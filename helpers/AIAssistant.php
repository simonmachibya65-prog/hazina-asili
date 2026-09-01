<?php
/**
 * AI Assistant Helper — integrates with OpenAI API (or compatible APIs like Gemini, Claude)
 * Provides compound analysis, research suggestions, and natural language Q&A.
 */
class AIAssistant {
    private string $apiKey;
    private string $apiUrl;
    private string $model;
    private bool $enabled;

    public function __construct() {
        $this->apiKey  = defined('AI_API_KEY') ? AI_API_KEY : '';
        $this->apiUrl  = defined('AI_API_URL') ? AI_API_URL : 'https://api.openai.com/v1/chat/completions';
        $this->model   = defined('AI_MODEL') ? AI_MODEL : 'gpt-3.5-turbo';
        $this->enabled = !empty($this->apiKey);
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }

    /**
     * Ask a general question about natural compounds.
     */
    public function ask(string $question, string $context = ''): array {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'AI assistant is not configured. Add AI_API_KEY to your .env file.'];
        }

        $systemPrompt = "You are a helpful AI assistant integrated into HAZINA ASILI (Natural Organic Compounds Database Management System). "
            . "You can answer any question on any topic — science, coding, general knowledge, research, etc. "
            . "Be concise, accurate, and helpful. Keep responses under 300 words unless the user asks for more detail.";

        if ($context) {
            $systemPrompt .= "\n\nAdditional context:\n" . $context;
        }

        return $this->chat($systemPrompt, $question);
    }

    /**
     * Analyze a compound and provide insights.
     */
    public function analyzeCompound(array $compound): array {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'AI assistant is not configured.'];
        }

        $prompt = "Analyze this natural compound and provide:\n"
            . "1. Known biological activities\n"
            . "2. Potential therapeutic applications\n"
            . "3. Related compounds in the same class\n"
            . "4. Safety/toxicity notes (if known)\n\n"
            . "Compound: {$compound['name']}\n"
            . "Formula: {$compound['formula']}\n"
            . "Molecular Weight: {$compound['molecular_weight']} g/mol\n"
            . (!empty($compound['organism_name']) ? "Source: {$compound['organism_name']}\n" : '')
            . (!empty($compound['description']) ? "Description: {$compound['description']}\n" : '');

        $system = "You are a phytochemistry expert. Provide scientifically accurate compound analysis. "
            . "Format your response with clear sections using markdown-style headers (##). Be concise but thorough.";

        return $this->chat($system, $prompt);
    }

    /**
     * Suggest research directions for a compound.
     */
    public function suggestResearch(array $compound): array {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'AI assistant is not configured.'];
        }

        $prompt = "Suggest 5 potential research directions or experiments for studying this compound:\n\n"
            . "Name: {$compound['name']}\n"
            . "Formula: {$compound['formula']}\n"
            . "MW: {$compound['molecular_weight']} g/mol\n"
            . (!empty($compound['organism_name']) ? "Source organism: {$compound['organism_name']}\n" : '')
            . "\nFor each suggestion, provide: a brief title, what it would investigate, and expected methodology.";

        $system = "You are a research advisor in natural products chemistry. Suggest practical, publishable research directions.";

        return $this->chat($system, $prompt);
    }

    /**
     * Predict properties based on molecular formula.
     */
    public function predictProperties(string $name, string $formula, float $mw): array {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'AI assistant is not configured.'];
        }

        $prompt = "Based on the molecular formula and weight, predict likely properties:\n\n"
            . "Name: {$name}\nFormula: {$formula}\nMW: {$mw} g/mol\n\n"
            . "Predict: compound class, likely functional groups, solubility characteristics, "
            . "and potential biological activity category. Note these are predictions and need experimental verification.";

        $system = "You are a computational chemistry assistant. Make reasonable predictions based on molecular information. "
            . "Always note that predictions require experimental verification.";

        return $this->chat($system, $prompt);
    }

    /**
     * Compare two compounds.
     */
    public function compareCompounds(array $compound1, array $compound2): array {
        if (!$this->enabled) {
            return ['success' => false, 'error' => 'AI assistant is not configured.'];
        }

        $prompt = "Compare these two natural compounds:\n\n"
            . "Compound 1: {$compound1['name']} ({$compound1['formula']}, MW: {$compound1['molecular_weight']})\n"
            . "Compound 2: {$compound2['name']} ({$compound2['formula']}, MW: {$compound2['molecular_weight']})\n\n"
            . "Compare: structural similarities, likely activity differences, shared compound class, and potential synergistic effects.";

        $system = "You are a comparative pharmacology expert. Provide clear structural and activity comparisons.";

        return $this->chat($system, $prompt);
    }

    /**
     * Core chat API call.
     */
    private function chat(string $systemPrompt, string $userMessage): array {
        $payload = json_encode([
            'model'    => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'max_tokens'  => 1000,
            'temperature' => 0.7,
        ]);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            error_log("AI Assistant cURL error: {$error}");
            return ['success' => false, 'error' => 'Failed to connect to AI service.'];
        }

        if ($httpCode !== 200) {
            error_log("AI Assistant HTTP {$httpCode}: {$response}");
            if ($httpCode === 429) {
                return ['success' => false, 'error' => 'Too many requests. Please wait a moment and try again.'];
            }
            return ['success' => false, 'error' => "AI service returned an error (HTTP {$httpCode})."];
        }

        $data = json_decode($response, true);
        $content = $data['choices'][0]['message']['content'] ?? null;

        if (!$content) {
            return ['success' => false, 'error' => 'No response from AI service.'];
        }

        return [
            'success' => true,
            'answer'  => $content,
            'tokens'  => $data['usage'] ?? null,
        ];
    }
}
