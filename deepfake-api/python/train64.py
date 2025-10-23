import os
import sys
import time
import json
import numpy as np  # type: ignore
from glob import glob
from PIL import Image  # type: ignore

import tensorflow as tf  # type: ignore
from tensorflow.keras import layers, models  # type: ignore

# ========================
# CLI Arguments
# ========================
if len(sys.argv) < 3:
    print(json.dumps({
        "status": "error",
        "message": "Usage: train64.py <dataset_path> <output_dir>"
    }))
    sys.exit(1)

dataset_path = sys.argv[1]   # dataset folder with images
output_dir = sys.argv[2]     # where to save trained model

os.makedirs(output_dir, exist_ok=True)
os.makedirs(os.path.join(output_dir, "samples"), exist_ok=True)

# ========================
# Hyperparameters
# ========================
IMG_SIZE = 64
LATENT_DIM = 100
BATCH_SIZE = 32
EPOCHS = 5       # adjust as needed
SAVE_FREQ = 1    # save every N epochs

# ========================
# Data Loader
# ========================
def load_images(path):
    images = []
    for file in glob(os.path.join(path, "*.png")) + glob(os.path.join(path, "*.jpg")):
        try:
            img = Image.open(file).convert("RGB").resize((IMG_SIZE, IMG_SIZE))
            img = np.asarray(img, dtype=np.float32) / 127.5 - 1.0
            images.append(img)
        except:
            continue
    return np.array(images)

print("Loading dataset from:", dataset_path)
images = load_images(dataset_path)
print(f"Loaded {len(images)} images")

dataset = tf.data.Dataset.from_tensor_slices(images).shuffle(1000).batch(BATCH_SIZE)

# ========================
# Build Generator
# ========================
def build_generator():
    model = models.Sequential([
        layers.Dense(8*8*256, use_bias=False, input_shape=(LATENT_DIM,)),
        layers.BatchNormalization(),
        layers.LeakyReLU(),
        layers.Reshape((8, 8, 256)),

        layers.Conv2DTranspose(128, (5, 5), strides=(2, 2), padding="same", use_bias=False),
        layers.BatchNormalization(),
        layers.LeakyReLU(),

        layers.Conv2DTranspose(64, (5, 5), strides=(2, 2), padding="same", use_bias=False),
        layers.BatchNormalization(),
        layers.LeakyReLU(),

        layers.Conv2DTranspose(3, (5, 5), strides=(2, 2), padding="same", use_bias=False, activation="tanh")
    ])
    return model

# ========================
# Build Discriminator
# ========================
def build_discriminator():
    model = models.Sequential([
        layers.Conv2D(64, (5, 5), strides=(2, 2), padding="same", input_shape=[64, 64, 3]),
        layers.LeakyReLU(),
        layers.Dropout(0.3),

        layers.Conv2D(128, (5, 5), strides=(2, 2), padding="same"),
        layers.LeakyReLU(),
        layers.Dropout(0.3),

        layers.Flatten(),
        layers.Dense(1)
    ])
    return model

generator = build_generator()
discriminator = build_discriminator()

cross_entropy = tf.keras.losses.BinaryCrossentropy(from_logits=True)
generator_optimizer = tf.keras.optimizers.Adam(1e-4)
discriminator_optimizer = tf.keras.optimizers.Adam(1e-4)

# ========================
# Training Step
# ========================
@tf.function
def train_step(images):
    noise = tf.random.normal([BATCH_SIZE, LATENT_DIM])

    with tf.GradientTape() as gen_tape, tf.GradientTape() as disc_tape:
        generated_images = generator(noise, training=True)

        real_output = discriminator(images, training=True)
        fake_output = discriminator(generated_images, training=True)

        gen_loss = cross_entropy(tf.ones_like(fake_output), fake_output)
        disc_loss = (cross_entropy(tf.ones_like(real_output), real_output) +
                     cross_entropy(tf.zeros_like(fake_output), fake_output))

    gradients_of_generator = gen_tape.gradient(gen_loss, generator.trainable_variables)
    gradients_of_discriminator = disc_tape.gradient(disc_loss, discriminator.trainable_variables)

    generator_optimizer.apply_gradients(zip(gradients_of_generator, generator.trainable_variables))
    discriminator_optimizer.apply_gradients(zip(gradients_of_discriminator, discriminator.trainable_variables))

# ========================
# Training Loop
# ========================
last_epoch_model = None
last_sample_path = os.path.join(output_dir, "samples", "final_sample.png")

for epoch in range(1, EPOCHS + 1):
    for image_batch in dataset:
        train_step(image_batch)

    if epoch % SAVE_FREQ == 0:
        # Save checkpoint with epoch suffix
        last_epoch_model = os.path.join(output_dir, f"generator_epoch{epoch}.h5")
        generator.save(last_epoch_model)
        print(f"Saved checkpoint (epoch {epoch}): {last_epoch_model}")

        # Overwrite the same sample image file each SAVE_FREQ
        noise = tf.random.normal([1, LATENT_DIM])
        sample = generator(noise, training=False)[0].numpy()
        sample = ((sample + 1.0) * 127.5).clip(0, 255).astype("uint8")
        Image.fromarray(sample).save(last_sample_path)
        print(f"Updated last sample image: {last_sample_path}")

# ========================
# Final Save
# ========================
if last_epoch_model:
    final_model_path = os.path.join(output_dir, "generator_final.h5")
    os.rename(last_epoch_model, final_model_path)
else:
    final_model_path = os.path.join(output_dir, "generator_final.h5")
    generator.save(final_model_path)

# Write training log
log_path = os.path.join(output_dir, "training_log.txt")
with open(log_path, "w") as f:
    f.write(f"Training completed at {time.ctime()}\n")
    f.write(f"Epochs: {EPOCHS}\n")

print(json.dumps({
    "status": "success",
    "message": "Training completed",
    "model_path": final_model_path,
    "log_path": log_path,
    "last_sample": last_sample_path
}))
