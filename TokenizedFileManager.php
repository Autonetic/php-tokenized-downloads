<?php
class TokenizedFileManager {
    /**
     * @var PDO Database connection
     */
    private $pdo;
    
    /**
     * @var string Directory where files will be saved
     */
    private $uploadDir;

    /**
     * Constructor
     *
     * @param string $dbHost Database host
     * @param string $dbName Database name
     * @param string $dbUser Database username
     * @param string $dbPass Database password
     * @param string $uploadDir Directory to save uploads (default "uploads/")
     *
     * @throws Exception When the database connection fails.
     */
    public function __construct($dbHost, $dbName, $dbUser, $dbPass, $uploadDir = 'uploads/')
    {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,  // throw exceptions on errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,        // fetch associative arrays
            PDO::ATTR_EMULATE_PREPARES   => false,                   // use native prepared statements if possible
        ];
        
        try {
            $this->pdo = new PDO($dsn, $dbUser, $dbPass, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
        
        // Normalize and set the upload directory.
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        
        // Create upload directory if it doesn't exist.
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    /**
     * Uploads a file, stores its metadata in the database, and returns a token.
     *
     * @param array $file Uploaded file info from $_FILES (e.g., $_FILES['userfile'])
     * @var bool $expiry If the file has an expiry or not.
     * @var string $expiry_date The date the file download link expires.
     * @return string The token that can be used to download the file.
     *
     * @throws Exception If the upload fails or database insertion fails.
     */
    public function uploadFile(array $file, string $uploaded_by, bool $expiry, $expiry_date)
    {
        // Check for any file upload errors.
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed with error code " . $file['error']);
        }
        
        if ($expiry == "") {
            die('EXPIRY ISSUE');
        }
        // Get original file name and determine its extension.
        $originalName = basename($file['name']);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        
        // Generate a unique file name to avoid collisions.
        $uniqueName = uniqid('', true) . ($extension ? ".{$extension}" : '');
        $destination = $this->uploadDir . $uniqueName;
        
        // Move the file to the target directory.
        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            throw new Exception("Failed to move uploaded file.");
        }
        
        // Generate a secure token for later download use.
        $token = $this->generateToken();
        
        // Insert file meta-data into the database.
        $stmt = $this->pdo->prepare("
            INSERT INTO files (original_name, file_name, token, uploaded_by, expiry, expiry_date, created_at)
            VALUES (:original_name, :file_name, :token, :uploaded_by, :expiry, :expiry_date, NOW())
        ");
        
        $stmt->execute([
            ':original_name' => $originalName,
            ':file_name'     => $uniqueName,
            ':token'         => $token,
            ':uploaded_by'   => $uploaded_by,
            ':expiry'        => $expiry,
            ':expiry_date'   => $expiry_date
        ]);
        
        return $token;
    }

    /**
     * Generates a random token. You can adjust the length as needed.
     *
     * @param int $length Number of random bytes (default 16, which produces a 32-character hex string)
     * @return string Hexadecimal token.
     */
    private function generateToken($length = 16)
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Returns file information by token.
     *
     * @param string $token The token provided for downloading.
     * @return array Associative array of file metadata including full file path.
     *
     * @throws Exception When no matching record is found or if the file does not exist.
     */
    public function getFileByToken($token)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM files WHERE token = :token");
        $stmt->execute([':token' => $token]);
        $fileData = $stmt->fetch();

        if (!$fileData) {
            throw new Exception("Invalid or expired token.");
        }
        
        $filePath = $this->uploadDir . $fileData['file_name'];
        if (!file_exists($filePath)) {
            throw new Exception("File not found.");
        }
        
        // Optionally, add logic here to expire the token after download or update a download counter.
        if ($fileData['expiry'] == true) {
            if(time() > strtotime($fileData['expiry_date'])) {
                //Maybe consider removing the token and file upload if token is expired.
                throw new Exception("Token expired. <br>" . "File's epiration date: " . $fileData['expiry_date'] );
            }
        }
        
        $fileData['file_path'] = $filePath;
        return $fileData;
    }

    /**
     * Creates a download link using the token.
     *
     * Note: This is a convenience method. In production, construct an absolute URL.
     *
     * @param string $token The token associated with the file.
     * @return string The URL for downloading the file.
     */
    public function createDownloadLink($token)
    {
        // Adjust the script name/path as needed for your application structure.
        $link = sprintf("download.php?token=%s", urlencode($token));
        return $link;
    }
    
    /**
     * Handles a token-based download.
     * This method sends appropriate headers and streams the file content.
     *
     * @param string $token The token provided in the download request.
     * @return void
     * @throws Exception if the token or file is invalid.
     */
    public function handleDownload($token) {
        $fileData = $this->getFileByToken($token);

        if (!file_exists($fileData['file_path'])) {
            throw new Exception("File not found.");
        }

        // Optionally, for one-time downloads, you might remove the token here...
        
        // Get the MIME type and set headers.
        $mimeType = mime_content_type($fileData['file_path']);
        header('Content-Description: File Transfer');
        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $fileData['original_name'] . '"'); //basename($filePath)
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fileData['file_path']));
        readfile($fileData['file_path']);
        exit;
    }
}
?>
