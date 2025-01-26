import cv2
import face_recognition
import os
import sys

# Get input arguments: photo to verify and directory of registered faces
if len(sys.argv) < 3:
    print("Error: Missing arguments. Usage: script.py <login_photo_path> <registered_faces_dir>")
    sys.exit(1)

login_photo_path = sys.argv[1]
registered_faces_dir = sys.argv[2]

# Verify that the login photo exists
if not os.path.exists(login_photo_path):
    print("Error: Login photo not found.")
    sys.exit(1)

# Load the login photo and compute its face encoding
login_image = face_recognition.load_image_file(login_photo_path)
login_encodings = face_recognition.face_encodings(login_image)

if len(login_encodings) == 0:
    print("Error: No face detected in login photo.")
    sys.exit(1)

login_encoding = login_encodings[0]

# Load registered face encodings
registered_encodings = []
registered_names = []

for file_name in os.listdir(registered_faces_dir):
    if file_name.lower().endswith(('.png', '.jpg', '.jpeg')):
        file_path = os.path.join(registered_faces_dir, file_name)
        image = face_recognition.load_image_file(file_path)
        encodings = face_recognition.face_encodings(image)

        if len(encodings) > 0:
            registered_encodings.append(encodings[0])
            registered_names.append(file_name)
        else:
            print(f"Warning: No face detected in {file_name}")

# Compare login encoding with registered encodings
match_found = False
for encoding, name in zip(registered_encodings, registered_names):
    match = face_recognition.compare_faces([encoding], login_encoding, tolerance=0.6)
    if match[0]:
        print("Match found: " + name)
        match_found = True
        break

if not match_found:
    print("No matching face found.")