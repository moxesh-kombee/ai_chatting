<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    /**
     * Chat endpoint that uses Hugging Face Router API (OpenAI compatible).
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array',
        ]);

        $apiToken = env('HUGGING_FACE_API_TOKEN');
        $url = env('HUGGING_FACE_URL');
        $model = env('HUGGING_FACE_MODEL');
        $userMessage = $request->input('message');
        $history = $request->input('history', []);

        // Detect if it's an error log pasting
        $isErrorLog = str_contains(strtolower($userMessage), 'error') ||
                      str_contains(strtolower($userMessage), 'exception') ||
                      str_contains(strtolower($userMessage), 'stack trace');

        $systemPrompt = 'You are a helpful Laravel assistant. ';
        if ($isErrorLog) {
            $systemPrompt .= 'The user has pasted a Laravel error log. Please analyze it, explain the cause, and provide step-by-step resolving steps.';
        } else {
            $systemPrompt .= 'Help the user with their queries about Laravel development.';
        }

        // Prepare messages for OpenAI-compatible format
        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
        ];

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg['role'] ?? 'user',
                'content' => $msg['content'] ?? $msg['message'] ?? '',
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $userMessage];

        try {
            $response = Http::timeout(120)->withHeaders([
                'Authorization' => "Bearer {$apiToken}",
                'Content-Type' => 'application/json',
            ])->post($url, [
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 800,
                'temperature' => 0.7,
            ]);

            if ($response->failed()) {
                Log::error('Hugging Face API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'error' => 'Failed to get response from AI.',
                    'details' => $response->json() ?? $response->body(),
                ], 500);
            }

            $result = $response->json();
            $reply = $result['choices'][0]['message']['content'] ?? 'No response generated.';

            return response()->json([
                'message' => $reply,
                'history' => array_merge($history, [
                    ['role' => 'user', 'content' => $userMessage],
                    ['role' => 'assistant', 'content' => $reply],
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Chat Exception', ['message' => $e->getMessage()]);

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }
}
