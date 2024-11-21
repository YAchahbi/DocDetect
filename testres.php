<?php

$json = '{
  "page_confidence_summary": [
    {
      "page_number": 1,
      "confidence_per_doc_type": {
        "DRIVER_LICENSE": 66.66666666666666
      },
      "image_saved": "output\\\\page_1_20241113_120543.jpg"
    },
    {
      "page_number": 2,
      "confidence_per_doc_type": {
        "CIN": 50.0
      },
      "image_saved": "output\\\\page_2_20241113_120602.jpg"
    }
  ]
}';

$data = json_decode($json, true);

echo "<table border='1'>";
echo "<tr><th>Page Number</th><th>Document Type</th><th>Confidence</th><th>Image Saved</th></tr>";

foreach ($data['page_confidence_summary'] as $page) {
    foreach ($page['confidence_per_doc_type'] as $docType => $confidence) {
        echo "<tr>";
        echo "<td>" . $page['page_number'] . "</td>";
        echo "<td>" . $docType . "</td>";
        echo "<td>" . $confidence . "</td>";
        echo "<td>" . $page['image_saved'] . "</td>";
        echo "</tr>";
    }
}

echo "</table>";
?>
