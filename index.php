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
        
        if(empty($_POST['username'])) {
            $error[] = "No username has been given!";
        }
        
        if(!isset($error)) {
            $username = trim($_POST["username"]);
            $expires = $_POST["expires"];
            if($expires == "yes") {
                $expires = TRUE;
                $expiry_date = $_POST["expiry"];
                $date = DateTime::createFromFormat('m/d/Y h A', $expiry_date);
                // Upload file and get token
                $token = $fileManager->uploadFile($_FILES['userfile'], $username, $expires, $date->format('Y-m-d H:i')); // Token is returned from a successfull upload
                $downloadLink = $fileManager->createDownloadLink($token);
                echo "File uploaded successfully! Download it here: <a href='$downloadLink'>$downloadLink</a>";
            } elseif($expires == "no") {
                $expires === FALSE;
                $expiry_date === NULL;
                $token = $fileManager->uploadFile($_FILES['userfile'], $username, $expires, $expiry_date);
                $downloadLink = $fileManager->createDownloadLink($token);
                echo "File uploaded successfully! Download it here: <a href='$downloadLink'>$downloadLink</a>";
            } else {
                $error[] = "Unrecognized input";
            }
        }
    }
} catch (Exception $e) {
    $error[] = "Error: " . $e->getMessage();
}

// Check for any errors
if(isset($error)) {
	foreach($error as $error) {
		echo $error . "<br>";//$error_msg->err_notificaton($error, 'danger');
	}
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
  <!-- Bootstrap 5 JS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Popperjs -->
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js" integrity="sha256-BRqBN7dYgABqtY9Hd4ynE+1slnEw+roEPFzQ7TRRfcg=" crossorigin="anonymous"></script>
  <!-- Tempus Dominus JavaScript -->
  <script src="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/js/tempus-dominus.min.js" crossorigin="anonymous"></script>
  <!-- Tempus Dominus Styles -->  
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@eonasdan/tempus-dominus@6.9.4/dist/css/tempus-dominus.min.css" crossorigin="anonymous">
  
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-Evv84Mr4kqVGRNSgIGL/F/aIDqQb7xQ2vcrdIwxfjThSH8CSR7PBEakCr51Ck+w+/U6swU2Im1vVX0SVk9ABhg==" crossorigin="anonymous" referrerpolicy="no-referrer" >
  
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" integrity="sha512-b+nQTCdtTBIRIbraqNEwsjB6UvL3UEMkXnhzd8awtCYh0Kcsjl9uEgwVFVbhoj3uu1DO1ZMacNvLoyJJiNfcvg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

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
        <label for="file" class="form-label">Choose a file</label>
        <input type="file" class="form-control" id="file" name="userfile" />
      </div>

      <div class="mb-3">
        <label for="expires" class="form-label">Download Link Expires?</label>
        <select name="expires" class="selectpicker form-select" id="expires">
          <option value="">Select an option</option>
          <option value="yes">Yes</option>
          <option value="no">No</option>
        </select>
      </div>

      <div class="row" style="max-width: 50%;">
        <div class="col-sm-12" id="htmlTarget">
          <label for="datetimepicker1Input" class="form-label">Pick an expiration date:</label>
          <div class="input-group log-event" id="datetimepicker1" data-td-target-input="nearest" data-td-target-toggle="nearest">
            <input id="datetimepicker1Input" type="text" class="form-control" data-td-target="#datetimepicker1" name="expiry" disabled>
            <span class="input-group-text" data-td-target="#datetimepicker1" data-td-toggle="datetimepicker">
              <i class="fas fa-calendar"></i>
            </span>
          </div>
        </div>
      </div>

      <button type="submit" name='upload' class="btn btn-primary">Upload</button>
    </form>
  </div>


  <script>
  new tempusDominus.TempusDominus(document.getElementById('datetimepicker1'), {
  localization: {
	locale: 'au',
	startOfTheWeek: 1
   },
 });
  </script>
  
  <script>
  document.getElementById('expires').addEventListener('change', function() {
    var inputField = document.getElementById('datetimepicker1Input');
    if (this.value === 'yes') {
      inputField.disabled = false;
    } else {
      inputField.disabled = true;
    }
  });
  </script>
</body>
</html>
