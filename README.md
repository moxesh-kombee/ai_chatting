# AI Chatting - Laravel Error Resolver

AI Chatting is a powerful Laravel-based API tool designed to assist developers with Laravel queries and provide instant, actionable steps for resolving Laravel error logs.

It integrates two major AI providers: **Hugging Face** and **Cohere**.

## 🚀 Features

- **Standard Chat**: Ask any Laravel-related questions.
- **Error Log Resolver**: Paste any Laravel error log (e.g., SQLSTATE errors, Exceptions), and the AI will analyze it to provide a cause and solution.
- **Multi-Provider Support**: Choose between Hugging Face (Mistral) and Cohere AI.
- **History Management**: Supports conversation context for follow-up questions.
- **Increased Timeouts**: Handles long-running AI generations (up to 120s).

---

## 🛠️ Setup

1. **Clone the Repository**:
   ```bash
   git clone https://github.com/your-username/ai_chatting.git
   cd ai_chatting
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Configure Environment**:
   Copy `.env.example` to `.env` and configure your API keys:
   ```env
   # Hugging Face Configuration
   HUGGING_FACE_API_TOKEN=your_token
   HUGGING_FACE_MODEL=mistralai/Mistral-7B-Instruct-v0.2
   HUGGING_FACE_URL=https://router.huggingface.co/v1/chat/completions

   # Cohere Configuration
   COHERE_API_KEY=your_key
   COHERE_MODEL=command-r-08-2024
   COHERE_URL=https://api.cohere.com/v2/chat
   ```

4. **Generate App Key**:
   ```bash
   php artisan key:generate
   ```

5. **Run Server**:
   ```bash
   php artisan serve
   ```

---

## 🔌 API Endpoints

### 1. Hugging Face Chat API
**URL**: `POST /api/chat`

Handles requests using Hugging Face's serverless router.

**Request Body**:
```json
{
    "message": "local.ERROR: SQLSTATE[HY000]: General error: 1 no such table: users",
    "history": []
}
```

### 2. Cohere Chat API
**URL**: `POST /api/chat-v2`

Handles requests using Cohere's V2 Chat API (Command R model).

**Request Body**:
```json
{
    "message": "How do I implement a protected route in Laravel?",
    "history": [
        {"role": "user", "content": "Tell me about middleware."}
    ]
}
```

---

## 🧪 Testing with Postman

A Postman collection is included in the root directory: `ai_chatting_postman_collection.json`. 
Import it into Postman to quickly test both endpoints.

## 📄 License
The MIT License (MIT).
