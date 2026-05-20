<?php namespace App\components;

class basichat {
    function __construct () {
        global $self, $doc;
        $self->take("components", "gov2nav", "setDefaultNav");
        $doc->body("pageTitle", 'Chat-RAG Component');

        // Configure global <cube-chat-rag> (rendered di cubeLayout sidepanel)
        // Twig vars di-binding ke props component — null/[]/{} sebagai default
        // saat halaman lain tidak set.
        $doc->body("chatRagEndpoint", "/components/basichat/dummy");
        $doc->body("chatRagPlaceholder", "Tanya tentang component ini…");
        $doc->body("chatRagGreeting", "Halo! Saya asisten demo. Backend ini dummy — tidak hit LLM, cuma return mock response.");
        $doc->body("chatRagPersistKey", json_encode("demo:basichat"));
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
        // POST JSON body: {query, context}
        $raw = file_get_contents('php://input');
        $body = json_decode($raw, true);
        if (!is_array($body)) { $body = $_POST; }

        $query = $body['query'] ?? '';
        $context = $body['context'] ?? [];

        // Simulate ~200ms LLM latency
        usleep(200000);

        return [
            'answer' => $this->_buildAnswer($query, $context),
            'chunks' => $this->_buildChunks(),
            'metadata' => [
                'model' => 'dummy-echo-v1',
                'duration_ms' => 200,
                'note' => 'Backend dummy — tidak ada LLM call. Lihat apps/components/basichat.php untuk implementasi.',
            ],
        ];
    }

    private function _buildAnswer (string $query, array $context): string {
        $q = $query !== '' ? $query : '(query kosong)';
        $ctxJson = empty($context) ? '_(tidak ada konteks)_' : '`' . json_encode($context, JSON_UNESCAPED_UNICODE) . '`';

        return "**Jawaban demo** untuk pertanyaan: _\"{$q}\"_\n\n"
             . "Konteks: {$ctxJson}\n\n"
             . "Component `<cube-chat-rag>` agnostik terhadap backend. Backend apa saja boleh — LLM real, "
             . "retrieval-only, atau dummy seperti ini — yang penting return contract:\n\n"
             . "```json\n"
             . "{\n"
             . "  \"answer\": \"string markdown\",\n"
             . "  \"chunks\": [{ file, heading_path, content_preview, score, ... }],\n"
             . "  \"metadata\": { model, duration_ms, ... }\n"
             . "}\n"
             . "```\n\n"
             . "**Markdown yang aman dipakai di `answer`:**\n\n"
             . "- **bold**, _italic_, `code inline`\n"
             . "- Bulleted + ordered list\n"
             . "- Code block dengan syntax\n"
             . "- [Link](https://example.com)\n\n"
             . "Untuk wire ke LLM real, ganti method `dummy()` dengan orchestration sesungguhnya, "
             . "atau set prop `endpoint` ke proxy server-side yang attach API key dari config.";
    }

    private function _buildChunks (): array {
        return [
            [
                'chunk_id' => 1001,
                'document_id' => 21,
                'file' => 'panduan_phpvb_komponen.pdf',
                'heading_path' => 'BAB I PENDAHULUAN > 1.1 Tujuan',
                'content_preview' => 'Component <cube-chat-rag> adalah sidepanel chat reusable. Pattern offcanvas memungkinkan tab utama tetap visible saat user chat — beda dari modal full-screen.',
                'score' => 0.92,
            ],
            [
                'chunk_id' => 1002,
                'document_id' => 21,
                'file' => 'panduan_phpvb_komponen.pdf',
                'heading_path' => 'BAB II ARSITEKTUR > 2.3 Backend Contract',
                'content_preview' => 'Component bersifat agnostik terhadap LLM provider. Consumer cukup expose REST endpoint yang return contract {answer, chunks, metadata}.',
                'score' => 0.78,
            ],
            [
                'chunk_id' => 1003,
                'document_id' => 22,
                'file' => 'cube_layout_pattern.md',
                'heading_path' => 'Sidepanel Offcanvas > Slot Multi-Panel',
                'content_preview' => 'Pattern slot mutual-exclusive: wilayah, instansi, pengaturan, chat. Trigger via openSidePanel(view) global helper.',
                'score' => 0.61,
            ],
        ];
    }
}
