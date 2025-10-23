import os, sys, time, json
import numpy as np  # type: ignore
from tensorflow.keras.models import load_model  # type: ignore
from PIL import Image  # type: ignore
import face_recognition  # type: ignore
from skimage.metrics import structural_similarity as ssim  # type: ignore
from random import sample

# ---------- Quality Check ----------
def is_good_image(img_arr, ref_imgs=None):
    gray = np.mean(img_arr, axis=2)

    # Sharpness
    sharpness = np.var(np.gradient(gray)[0]) + np.var(np.gradient(gray)[1])

    # Brightness
    mean_val = gray.mean()

    # Contrast
    contrast = gray.std()

    # ---------- Looser thresholds ----------
    if sharpness < 100:   # was 150
        return False
    if mean_val < 70 or mean_val > 190:  # was 90–170
        return False
    if contrast < 30:  # was 50
        return False

    # SSIM against good references
    if ref_imgs is not None and len(ref_imgs) > 0:
        from skimage.transform import resize  # type: ignore
        img_resized = resize(img_arr, (128, 128), anti_aliasing=True)

        for ref in ref_imgs:
            ref_resized = resize(ref, (128, 128), anti_aliasing=True)
            score = ssim(img_resized, ref_resized, channel_axis=-1, data_range=1.0)
            if score > 0.45:  # was 0.60
                return True
        return False

    return True



# ---------- Face Landmark Check ----------
def has_valid_face(img_arr):
    try:
        faces = face_recognition.face_landmarks(img_arr)
        if not faces:
            return False
        for face in faces:
            if "left_eye" in face and "right_eye" in face and "nose_tip" in face:
                return True
        return False
    except Exception:
        return False


# ---------- Main Script ----------
def main():
    if len(sys.argv) < 3:
        print(json.dumps({"status": "error", "message": "Usage: infer.py <model_key> <count>"}))
        return

    model_key = sys.argv[1]
    count = int(sys.argv[2])

    # Paths
    base_dir = os.path.dirname(os.path.abspath(__file__))
    gan_dir = os.path.join(base_dir, "GANmodels")
    output_dir = os.path.join(base_dir, "..", "storage", "app", "public", "generated", model_key)
    good_pool_dir = os.path.join(base_dir, "..", "storage", "app", "public", "good_pool", model_key)
    rejected_dir = os.path.join(base_dir, "..", "storage", "app", "public", "rejected", model_key)

    os.makedirs(output_dir, exist_ok=True)
    os.makedirs(rejected_dir, exist_ok=True)

    # Track used fallback files
    used_pool_files = set()

    # Load good_pool refs for SSIM
    ref_imgs = []
    if os.path.exists(good_pool_dir):
        pool_files = [f for f in os.listdir(good_pool_dir) if f.lower().endswith((".png", ".jpg"))]
        for f in pool_files[:5]:  # use up to 5 references
            try:
                ref = np.array(Image.open(os.path.join(good_pool_dir, f)).convert("RGB"))
                ref_imgs.append(ref)
            except:
                continue

    # Try multiple filename patterns
    candidates = [
        f"generator_{model_key}_final(2K).h5",
        f"generator_{model_key}_final2K.h5",
        f"generator_{model_key}_final(2k).h5",
        f"generator_{model_key}_final.h5",
        f"generator_{model_key}.h5",
    ]

    model_path = None
    for name in candidates:
        path = os.path.join(gan_dir, name)
        if os.path.exists(path):
            model_path = path
            break

    if not model_path:
        print(json.dumps({
            "status": "error",
            "message": f"No model file found for {model_key} in GANmodels/"
        }))
        return

    # Load model
    generator = load_model(model_path)

    LATENT_DIM = 100
    results = []

    max_candidates = count * 15  # oversample even more
    for i in range(max_candidates):
        if len(results) >= count:
            break

        z = np.random.normal(0, 1, (1, LATENT_DIM))
        img = generator.predict(z, verbose=0)[0]

        # Scale
        if img.min() >= -1.1 and img.max() <= 1.1:
            img = (img * 127.5 + 127.5)
        else:
            img = img * 255.0
        img = img.clip(0, 255).astype("uint8")

        # ---- Ultra-strict filtering ----
        if is_good_image(img, ref_imgs) and has_valid_face(img):
            ts = int(time.time() * 1000)
            filename = f"{model_key}_{ts}_{i}.png"
            out_path = os.path.join(output_dir, filename)
            Image.fromarray(img).save(out_path)

            results.append({
                "path": f"storage/generated/{model_key}/{filename}",
                "source": "generated"
            })
        else:
            ts = int(time.time() * 1000)
            reject_name = f"{model_key}_reject_{ts}_{i}.png"
            reject_path = os.path.join(rejected_dir, reject_name)
            Image.fromarray(img).save(reject_path)

            # fallback (unique selection)
            if os.path.exists(good_pool_dir):
                pool_files = [f for f in os.listdir(good_pool_dir) if f.lower().endswith((".png", ".jpg"))]
                pool_files = list(set(pool_files) - used_pool_files)
                if pool_files:
                    pool_choice = np.random.choice(pool_files)
                    used_pool_files.add(pool_choice)
                    results.append({
                        "path": f"storage/good_pool/{model_key}/{pool_choice}",
                        "source": "good_pool"
                    })

    # Final fallback if too strict
    if len(results) < count and os.path.exists(good_pool_dir):
        pool_files = [f for f in os.listdir(good_pool_dir) if f.lower().endswith((".png", ".jpg"))]
        pool_files = list(set(pool_files) - used_pool_files)
        needed = count - len(results)
        if len(pool_files) >= needed:
            chosen = sample(pool_files, needed)  # random unique files
        else:
            chosen = pool_files  # take whatever remains
        for f in chosen:
            used_pool_files.add(f)
            results.append({
                "path": f"storage/good_pool/{model_key}/{f}",
                "source": "good_pool"
            })

    if len(results) == 0:
        print(json.dumps({"status": "error", "message": "No good images found"}))
    else:
        print(json.dumps({
            "status": "success",
            "filenames": results,
            "generated_count": sum(1 for r in results if r["source"] == "generated"),
            "good_pool_count": sum(1 for r in results if r["source"] == "good_pool"),
            "rejected_saved": len(os.listdir(rejected_dir))
        }))


if __name__ == "__main__":
    main()
