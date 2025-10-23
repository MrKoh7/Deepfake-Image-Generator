import os
import sys
import json
import numpy as np  # type: ignore
from PIL import Image  # type: ignore
import tensorflow as tf  # type: ignore
from tensorflow.keras.models import load_model  # type: ignore

# ========================
# CLI Arguments
# ========================
if len(sys.argv) < 3:
    print(json.dumps({
        "status": "error",
        "message": "Usage: test_generator.py <model_path> <output_dir>"
    }))
    sys.exit(1)

model_path = sys.argv[1]     # Trained generator .h5
output_dir = sys.argv[2]     # Where to save the test image

os.makedirs(output_dir, exist_ok=True)

# ========================
# Constants
# ========================
LATENT_DIM = 100
out_path = os.path.join(output_dir, "final_sample.png")  # ✅ renamed here

try:
    # 🔹 Validate model path first
    if not os.path.exists(model_path):
        print(json.dumps({
            "status": "error",
            "message": f"Generator model not found: {model_path}"
        }))
        sys.exit(1)

    # 🔹 Always clear old output
    if os.path.exists(out_path):
        os.remove(out_path)

    # 🔹 Load generator model
    generator = load_model(model_path, compile=False)

    # 🔹 Generate random noise
    noise = tf.random.normal([1, LATENT_DIM])
    generated = generator(noise, training=False)[0].numpy()

    # 🔹 Convert to uint8 image
    img = ((generated + 1.0) * 127.5).clip(0, 255).astype("uint8")
    img = Image.fromarray(img)

    # 🔹 Save test output
    img.save(out_path)

    print(json.dumps({
        "status": "success",
        "message": "Test image generated",
        "image_path": out_path
    }))
except Exception as e:
    print(json.dumps({
        "status": "error",
        "message": str(e)
    }))
    sys.exit(1)
