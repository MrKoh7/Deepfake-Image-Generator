# DeepFakeGEN 🤖

A full-stack deepfake face image generation and custom GAN model training web application built as a Final Year Project. The system addresses the lack of ethnicity diversity in existing deepfake generators by training dedicated DCGAN models across multiple ethnicities and genders.

> ⚠️ **Ethical Disclaimer**: This project was built purely for academic research purposes. Understanding synthetic media generation is essential context for building detection systems. The author does not condone misuse of deepfake technology for deception, fraud, or harm of any kind.

---

## 📹 Demo

> [Demo Video (YouTube)](YOUR_DEMO_VIDEO_LINK_HERE)

---

## ✨ Features

- **Diverse Deepfake Image Generation** — Generate synthetic face images from 5 pre-trained GAN models covering multiple ethnicities and genders
- **Custom Model Training** — Upload your own image dataset to train a personalized GAN generator model
- **Model Testing** — Test your trained model with a sample output image after training completes
- **Downloadable Outputs** — Download generated images or trained models as ZIP files
- **User Authentication** — Session-based registration and login system with role-based access control
- **Image Quality Filtering** — Automated quality checking pipeline (sharpness, brightness, contrast, facial landmark detection, SSIM scoring) to ensure only good images are shown to users

---

## 🧠 GAN Models

| Model | Ethnicity | Gender |
|-------|-----------|--------|
| Asian Female | Asian | Female |
| Asian Male | Asian | Male |
| European Female | European | Female |
| European Male | European | Male |
| African Male | African | Male |

### Model Architecture (DCGAN)

The system uses a **Deep Convolutional GAN (DCGAN)** architecture:

- **Generator**: Takes a random latent noise vector (100 dimensions) and progressively upsamples through dense and transposed convolutional layers to produce a **64×64 RGB face image**
- **Discriminator**: Classifies images as real (from training dataset) or fake (from generator) using convolutional layers with LeakyReLU activation, batch normalization, and dropout
- **Training**: Adversarial min-max game — generator learns to fool the discriminator; discriminator learns to detect fakes
- **Loss Function**: Binary cross-entropy with Adam optimizer for both networks

### Hyperparameters

```python
IMG_SIZE    = 64       # Output image resolution
LATENT_DIM  = 100      # Random noise input dimension
BATCH_SIZE  = 32       # Images per training step
EPOCHS      = 20000    # Training epochs for pre-trained models
SAVE_FREQ   = 1000     # Checkpoint save frequency
```

### Training Infrastructure

Pre-trained models were trained on **Google Colab** using an **NVIDIA A100 GPU**. Custom user model training runs on the local host CPU.

---

## 🛠️ Tech Stack

| Layer | Technology |
|-------|------------|
| Frontend | Vue.js 3 (Composition API), Axios, CSS |
| Backend | PHP Laravel 8.83.29, RESTful API |
| AI Engine | Python 3.10, TensorFlow/Keras, DCGAN |
| Database | MySQL (WampServer) |
| Process Bridge | Symfony Process Component |
| File Storage | Laravel Built-in Storage System |
| Training (Pre-trained) | Google Colab (NVIDIA A100 GPU) |

---

## 🏗️ System Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    Vue.js Frontend                          │
│         (Generated.vue / Training.vue / Auth)               │
└─────────────────────┬───────────────────────────────────────┘
                      │ Axios HTTP (localhost:5173)
                      ▼
┌─────────────────────────────────────────────────────────────┐
│                  Laravel Backend API                        │
│  /api/generate  /api/train-generator  /api/upload-dataset   │
│  /api/test-generator  /api/download-zip  /api/register      │
└──────────┬──────────────────────────────────┬───────────────┘
           │ Symfony Process                  │ Eloquent ORM
           ▼                                  ▼
┌─────────────────────┐             ┌─────────────────────────┐
│   Python Scripts    │             │     MySQL Database      │
│  infer.py           │             │  users table            │
│  train64.py         │             │  (credentials)          │
│  test_generator.py  │             └─────────────────────────┘
└─────────────────────┘
           │
           ▼
┌─────────────────────┐
│   GAN Models        │
│  GANmodels/         │
│  *.h5 weights       │
└─────────────────────┘
```

---

## 📁 Project Structure

```
FYPImageGenerator/
├── deepfake-api/                  # Laravel PHP Backend
│   ├── app/Http/Controllers/
│   │   ├── GenerationController.php   # Image generation API
│   │   ├── TrainingController.php     # Model training API
│   │   ├── DatasetController.php      # Dataset upload API
│   │   ├── TestController.php         # Model testing API
│   │   ├── ZipController.php          # ZIP download API
│   │   └── AuthController.php         # Login/logout/register
│   ├── python/
│   │   ├── GANmodels/                 # Pre-trained .h5 model weights
│   │   ├── infer.py                   # Image generation script
│   │   ├── train64.py                 # GAN training script
│   │   └── test_generator.py          # Model testing script
│   ├── storage/app/public/
│   │   ├── generated/                 # Generated images by ethnicity
│   │   ├── good_pool/                 # High-quality fallback images
│   │   ├── rejected/                  # Filtered low-quality images
│   │   ├── datasets/                  # User uploaded training datasets
│   │   └── models/                    # Trained models + logs + ZIPs
│   └── database/migrations/           # User table migration
│
└── deepfake-frontend/             # Vue.js Frontend
    └── src/
        ├── components/
        ├── views/
        │   ├── Generated.vue          # Image generation page
        │   └── Training.vue           # Model training page
        ├── router/index.js            # Route guards + navigation
        └── api.js                     # Axios config + API helpers
```

---

## 🚀 Local Setup

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- Python 3.10
- WampServer (MySQL + Apache)

### 1. Clone the Repository

```bash
git clone https://github.com/YOUR_USERNAME/FYPImageGenerator.git
cd FYPImageGenerator
```

### 2. Backend Setup (Laravel)

```bash
cd deepfake-api

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate

# Configure your database in .env
# DB_DATABASE=deepfakeapp
# DB_USERNAME=root
# DB_PASSWORD=

# Create database in phpMyAdmin first, then run migrations
php artisan migrate

# Start Laravel server
php artisan serve
# Runs at http://127.0.0.1:8000
```

### 3. Frontend Setup (Vue.js)

```bash
cd deepfake-frontend

# Install dependencies
npm install

# Start development server
npm run dev
# Runs at http://localhost:5173
```

### 4. Python Environment Setup

```bash
cd deepfake-api/python

# Create virtual environment
python -m venv venv

# Activate (Windows)
venv\Scripts\activate

# Install dependencies
pip install tensorflow keras numpy pillow opencv-python scikit-image dlib
```

### 5. Access the Application

Open your browser at `http://localhost:5173`

---

## 🔌 API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/generate` | Generate deepfake images from pre-trained model |
| POST | `/api/upload-dataset` | Upload image dataset for training |
| POST | `/api/train-generator` | Train a custom GAN generator model |
| POST | `/api/test-generator` | Test a trained model with sample output |
| POST | `/api/download-zip` | Download generated images as ZIP |
| POST | `/api/register` | Register a new user account |
| POST | `/api/login` | Login with credentials |
| POST | `/api/logout` | Logout and destroy session |
| GET | `/api/me` | Get current authenticated user |

---

## 🔍 Image Quality Pipeline (infer.py)

Generated images go through an automated quality checking pipeline before being shown to users:

1. **Grayscale conversion** for analysis
2. **Sharpness check** — Laplacian variance threshold
3. **Brightness check** — mean pixel intensity range
4. **Contrast check** — standard deviation threshold
5. **SSIM similarity score** — compared against good_pool reference images
6. **Facial landmark detection** — verifies presence of both eyes and nose

Images that fail quality checks are stored in the `rejected/` folder and replaced by images from the `good_pool/` of pre-curated high-quality images.

---

## ⚠️ Known Limitations

| Limitation | Reason | Production Solution |
|------------|--------|---------------------|
| Max 20 images per generation | CPU inference speed on local server | GPU-enabled cloud hosting (AWS SageMaker, Google Cloud Vertex AI) |
| 64×64 output resolution | Compute constraints during training | Progressive GAN or StyleGAN architecture with more GPU time |
| Local deployment only | ML model hosting complexity | Containerised deployment with GPU support |
| Custom training runs on CPU | User machine resources vary | Async job queue with cloud GPU workers |

---

## 🔮 Future Improvements

- Higher resolution output (256×256+) using Progressive GAN or StyleGAN2
- Larger, more diverse training datasets per ethnicity category
- Async training job queue for better user experience during long training runs
- Deepfake detection classifier alongside the generator — completing the ethical full picture
- Cloud deployment with GPU inference for production-grade performance

---

## ⚖️ Ethical Considerations

This system was developed strictly for academic research. Key ethical considerations addressed in the FYP:

- **Misuse Risk**: Deepfake technology can be used for fraud, non-consensual imagery, and misinformation. This project includes authentication to limit access.
- **Bias Awareness**: Existing tools disproportionately represent certain demographics. This project explicitly addresses ethnicity diversity as a core objective.
- **Responsible Use**: The system is intended for researchers and developers working on deepfake detection and understanding synthetic media.
- **Detection Context**: Understanding how synthetic media is generated is a prerequisite for building effective detection systems.

---

## 📚 Academic Context

**Institution**: [Your University Name]  
**Year**: 2025  
**Module**: Final Year Project  
**Grade**: [Your Grade — optional]

---

## 📄 License

This project is for academic purposes only. Not licensed for commercial use.
