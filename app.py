import os
import mysql.connector
from fastapi import FastAPI, UploadFile, File, HTTPException
from fastapi.responses import JSONResponse
import easyocr
import cv2
import numpy as np
from pdf2image import convert_from_bytes
from datetime import datetime
from collections import defaultdict
import uvicorn

app = FastAPI()

reader = easyocr.Reader(['fr'])

db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',
    'database': 'py_doc'
}

output_dir = "output"
os.makedirs(output_dir, exist_ok=True)

@app.post("/process_pdf/")
async def process_pdf(
    file: UploadFile = File(..., description="PDF file to process")
) -> JSONResponse:
    try:
        if not file.content_type or file.content_type != "application/pdf":
            raise HTTPException(status_code=400, detail="Invalid file format. Only PDF files are supported.")

        pdf_contents = await file.read()
        if not pdf_contents:
            raise HTTPException(status_code=400, detail="Empty file uploaded")

        poppler_path = r"C:\Users\msi\Downloads\poppler-24.08.0\Library\bin"
        
        # Convertir le PDF en images
        images = convert_from_bytes(pdf_contents, poppler_path=poppler_path)
        if not images:
            raise HTTPException(status_code=400, detail="Could not extract images from PDF. Ensure the file is valid.")

        all_results = []
        page_confidence_summary = []
        total_keywords_per_doc_type = defaultdict(int)
        matched_keywords_per_doc_type = defaultdict(int)
        total_texts = 0

        for i, image in enumerate(images):
            open_cv_image = cv2.cvtColor(np.array(image), cv2.COLOR_RGB2BGR)

            all_text_details = []
            matched_keywords_for_page = defaultdict(int)
            total_keywords_for_page = defaultdict(int)

            try:
                results = reader.readtext(open_cv_image)
            except Exception as e:
                raise HTTPException(status_code=500, detail=f"OCR processing error: {str(e)}")

            for (bbox, text, confidence) in results:
                text_upper = text.upper()
                matched_keywords, total_keywords = check_keyword_match(text_upper)

                total_texts += 1
                for doc_type, count in matched_keywords.items():
                    matched_keywords_per_doc_type[doc_type] += count
                    total_keywords_per_doc_type[doc_type] += total_keywords[doc_type]
                    matched_keywords_for_page[doc_type] += count
                    total_keywords_for_page[doc_type] += total_keywords[doc_type]

                all_text_details.append({
                    "text": text_upper,
                    "confidence": float(confidence),
                    "matched_keywords": list(matched_keywords.keys())
                })

                insert_document(file.filename, "", "", text_upper, confidence)

            timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
            image_path = os.path.join(output_dir, f"page_{i + 1}_{timestamp}.jpg")
            image.save(image_path, "JPEG")

            all_results.append({
                "image_saved": image_path,
                "page_number": i + 1,
                "all_text_details": all_text_details
            })

            page_confidence_summary.append({
                "page_number": i + 1,
                "confidence_per_doc_type": {
                    doc_type: (matched_keywords_for_page[doc_type] / total_keywords_for_page[doc_type]) * 100 
                    if total_keywords_for_page[doc_type] > 0 else 0
                    for doc_type in matched_keywords_for_page
                },
                "image_saved": image_path
            })

        # Calculer le pourcentage de confiance global
        global_confidence_percentage = {
            "overall_confidence_per_doc_type": {
                doc_type: (matched_keywords_per_doc_type[doc_type] / total_keywords_per_doc_type[doc_type]) * 100 
                if total_keywords_per_doc_type[doc_type] > 0 else 0
                for doc_type in matched_keywords_per_doc_type
            },
            "page_confidence_summary": page_confidence_summary
        }

        response_content = {
            "All Results": all_results,
            "global_confidence": global_confidence_percentage
        }

        return JSONResponse(content=response_content, status_code=200)

    except HTTPException as he:
        raise he
    except Exception as e:
        raise HTTPException(status_code=500, detail=f"An unexpected error occurred: {str(e)}")

def check_keyword_match(text: str):
    matched_keywords = defaultdict(int)
    total_keywords_per_doc_type = defaultdict(int)
    
    try:
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()

        query = "SELECT keyword, doc_type FROM keywords"
        cursor.execute(query)
        results = cursor.fetchall()

        for keyword, doc_type in results:
            if keyword.upper() in text:
                matched_keywords[doc_type] += 1
            total_keywords_per_doc_type[doc_type] += 1

    except mysql.connector.Error as err:
        print(f"Error: {err}")
    finally:
        if cursor:
            cursor.close()
        if connection:
            connection.close()

    return matched_keywords, total_keywords_per_doc_type

def insert_document(file_name, doc_type, image_path, extracted_text, confidence):
    try:
        connection = mysql.connector.connect(**db_config)
        cursor = connection.cursor()

        insert_query = """
        INSERT INTO documents (file_name, document_type, image_path, extracted_text, confidence_score)
        VALUES (%s, %s, %s, %s, %s)
        """
        cursor.execute(insert_query, (file_name, doc_type, image_path, extracted_text, confidence))
        connection.commit()

    except mysql.connector.Error as err:
        print(f"Error: {err}")
    finally:
        if cursor:
            cursor.close()
        if connection:
            connection.close()

if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=1080)
