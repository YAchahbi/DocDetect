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
    <h2>Charger le pdf</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="form-group">
            <label for="pdfFile">Choisissez votre pdf:</label>
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

                // Display CIN results
                if (!empty($responseData['results']['CIN'])) {
                    echo "<h6>CIN Results:</h6>";
                    foreach ($responseData['results']['CIN'] as $cinResult) {
                        echo "<div class='card mb-3'>";
                        echo "<div class='card-body'>";
                        echo "<h5 class='card-title'>File: " . htmlspecialchars($cinResult['file_name']) . "</h5>";
                        echo "<p><strong>Image Saved:</strong> <img src='" . htmlspecialchars($cinResult['image_saved']) . "' alt='CIN Image' style='width:100%; max-width:300px;'/></p>";
                        echo "<h6>Details:</h6><ul>";
                        foreach ($cinResult['details'] as $detail) {
                            echo "<li>" . htmlspecialchars($detail['text']) . " (Confidence: " . htmlspecialchars($detail['confidence']) . ")</li>";
                        }
                        echo "</ul></div></div>";
                    }
                }

                // Display Relevé results
                if (!empty($responseData['results']['Releve Note'])) {
                    echo "<h6>Relevé Results:</h6>";
                    foreach ($responseData['results']['Releve Note'] as $releveResult) {
                        echo "<div class='card mb-3'>";
                        echo "<div class='card-body'>";
                        echo "<h5 class='card-title'>File: " . htmlspecialchars($releveResult['file_name']) . "</h5>";
                        echo "<p><strong>Image Saved:</strong> <img src='" . htmlspecialchars($releveResult['image_saved']) . "' alt='Relevé Image' style='width:100%; max-width:300px;'/></p>";
                        echo "<h6>Details:</h6><ul>";
                        foreach ($releveResult['details'] as $detail) {
                            echo "<li>" . htmlspecialchars($detail['text']) . " (Confidence: " . htmlspecialchars($detail['confidence']) . ")</li>";
                        }
                        echo "</ul></div></div>";
                    }
                }

                echo "</div>"; 
            } else {
                echo "<div class='alert alert-warning'>Aucune response du server.</div>";
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
