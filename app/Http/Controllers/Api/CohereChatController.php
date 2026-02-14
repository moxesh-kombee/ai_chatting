<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CohereChatController extends Controller
{
    /**
     * Chat endpoint that uses Cohere API v2.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'history' => 'nullable|array',
        ]);

        $apiKey = env('COHERE_API_KEY');
        $url = env('COHERE_URL', 'https://api.cohere.com/v2/chat');
        $model = env('COHERE_MODEL', 'command-r-plus');

        if (! $apiKey || $apiKey === 'YOUR_COHERE_API_KEY_HERE') {
            return response()->json([
                'error' => 'Cohere API Key is missing. Please set COHERE_API_KEY in your .env file.',
            ], 422);
        }

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

        // Prepare messages for Cohere v2 format (OpenAI-like)
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
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])->post($url, [
                'model' => $model,
                'messages' => $messages,
            ]);

            if ($response->failed()) {
                Log::error('Cohere API Error', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                return response()->json([
                    'error' => 'Failed to get response from AI.',
                    'details' => $response->json() ?? $response->body(),
                ], 500);
            }

            $result = $response->json();

            // Cohere v2 response structure: result.message.content[0].text
            $reply = $result['message']['content'][0]['text'] ?? 'No response generated.';

            return response()->json([
                'message' => $reply,
                'history' => array_merge($history, [
                    ['role' => 'user', 'content' => $userMessage],
                    ['role' => 'assistant', 'content' => $reply],
                ]),
            ]);

        } catch (\Exception $e) {
            Log::error('Cohere Chat Exception', ['message' => $e->getMessage()]);

            return response()->json([
                'error' => 'An unexpected error occurred.',
                'exception' => $e->getMessage(),
            ], 500);
        }
    }
}
