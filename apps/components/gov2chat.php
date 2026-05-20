<?php namespace App\components;

class gov2chat {
    function __construct () {
        global $self, $doc;
        $self->take("components", "gov2nav", "setDefaultNav");
        $doc->body("pageTitle", 'Chat-RAG Component');

        // Configure global <cube-chat-rag> (rendered di cubeLayout sidepanel)
        // Twig vars di-binding ke props component — null/[]/{} sebagai default
        // saat halaman lain tidak set.
        $doc->body("chatRagEndpoint", "/components/gov2chat/dummy");
        $doc->body("chatRagPlaceholder", "Tanya tentang component ini…");
        $doc->body("chatRagGreeting", "Halo! Saya asisten demo. Backend ini dummy — tidak hit LLM, cuma return mock response.");
        $doc->body("chatRagPersistKey", json_encode("demo:gov2chat"));
        $doc->body("chatRagSuggestedQueries", json_encode([
            "Apa itu cube-chat-rag?",
            "Bagaimana integrasi ke aplikasi consumer?",
            "Markdown apa saja yang didukung?",
        ]));
        $doc->body("chatRagContext", json_encode([
            "source" => "demo-page",
        ]));

        $self->content();
    }

    function dummy ($vars = "") {
        global $self;
        // POST JSON body: {query, context}
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        if (!is_array($body)) { $body = $_POST; }

        $query = $body['query'] ?? '';
        $context = $body['context'] ?? [];

        // Simulate ~200ms LLM latency
        usleep(200000);

        return [
            'answer' => $self->buildAnswer($query, $context),
            'chunks' => $self->buildChunks(),
            'metadata' => [
                'model' => 'dummy-echo-v1',
                'duration_ms' => 200,
                'note' => 'Backend dummy — tidak ada LLM call. Lihat apps/components/model/gov2chat.php untuk implementasi.',
            ],
        ];
    }
}
