<?php
// Include the class file if it is in a separate file
require_once 'TokenizedFileManager.php';

// Configure your database connection details
$dbHost = 'localhost';
$dbName = 'lococloud';
$dbUser = 'loco';
$dbPass = '#1234567*()';

try {
    $fileManager = new TokenizedFileManager($dbHost, $dbName, $dbUser, $dbPass);

    // Using a form submission with file input named 'userfile'
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['userfile'])) {
        // Upload file and get token
        $token = $fileManager->uploadFile($_FILES['userfile']); // Token is returned from a successfull upload
        $downloadLink = $fileManager->createDownloadLink($token);
        echo "File uploaded successfully! Download it here: <a href='$downloadLink'>$downloadLink</a>";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Testing upload form</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap 5 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

</head>
<body>
  <div class="container mt-5">
    <h2 class="mb-4">File Upload Form</h2>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"],ENT_QUOTES | ENT_HTML5, 'UTF-8', false); ?>" method="post" enctype="multipart/form-data">
      
      <input type="hidden" name="MAX_FILE_SIZE" value="3145728" />
      
      <div class="mb-3">
        <label for="name" class="form-label">Username</label>
        <input type="text" class="form-control" name="username" id="username" placeholder="Enter your name" />
      </div>

      <div class="mb-3">
        <label for="file" class="form-label">Chose a file</label>
        <input type="file" class="form-control" id="file" name="userfile" />
      </div>

      <button type="submit" name='upload' class="btn btn-primary">Upload</button>
    </form>
  </div>

  <!-- Bootstrap 5 JS and Popper CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
