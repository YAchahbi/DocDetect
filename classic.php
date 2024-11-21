<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload PDF</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5">
    <h2>Upload PDF for Processing</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="pdfFile">Select PDF File:</label>
            <input type="file" class="form-control" id="pdfFile" name="pdfFile" accept="application/pdf" required>
        </div>
        <button type="submit" class="btn btn-primary">Upload</button>
    </form>

    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['pdfFile'])) {

        $url = "http://localhost:8000/process_pdf/";

        $filePath = $_FILES['pdfFile']['tmp_name'];
        $fileName = $_FILES['pdfFile']['name'];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        if (file_exists($filePath)) {
            $cFile = new CURLFile($filePath, 'application/pdf', $fileName);
            $data = ['file' => $cFile];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            echo "<div class='alert alert-danger'>File not found.</div>";
        }

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo '<div class="alert alert-danger">cURL Error: ' . curl_error($ch) . '</div>';
        } else {

            $responseData = json_decode($response, true);
            if (isset($responseData['results'])) {
                echo "<div class='alert alert-success'>";
                echo "<h5>Response from Server:</h5>";
                echo "<pre>" . print_r($responseData, true) . "</pre>";
                echo "</div>";
            } else {
                echo "<div class='alert alert-warning'>Unexpected response from server.</div>";
            }
        }

        curl_close($ch);
    }
    ?>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
