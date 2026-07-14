<template>
  <div class="h-100 d-flex flex-column cube-chat-rag">

    <!-- Header: title + clear button -->
    <div class="px-3 py-2 border-bottom d-flex align-items-center justify-content-between flex-shrink-0 bg-light">
      <span class="small text-muted">
        <i class="bi bi-chat-dots me-1"></i>{{ history.length }} pesan
      </span>
      <button v-if="history.length > 0" class="btn btn-sm btn-outline-secondary py-0 px-2"
              @click="clearHistory" title="Hapus riwayat">
        <i class="bi bi-trash small"></i>
      </button>
    </div>

    <!-- Greeting + suggested queries (only when history empty) -->
    <div v-if="history.length === 0" class="px-3 py-3 flex-shrink-0">
      <div v-if="greeting" class="small mb-3">{{ greeting }}</div>
      <div v-if="suggestedQueries && suggestedQueries.length > 0" class="d-flex flex-column gap-2">
        <button v-for="(q, i) in suggestedQueries" :key="i"
                class="btn btn-sm btn-outline-primary text-start"
                style="font-size:0.8rem;white-space:normal"
                @click="pickSuggested(q)" :disabled="loading">
          <i class="bi bi-arrow-right-short me-1"></i>{{ q }}
        </button>
      </div>
    </div>

    <!-- Message list (scroll area) -->
    <div ref="messageList" class="flex-grow-1 overflow-auto px-3 py-2" style="min-height:0">
      <div v-for="(msg, idx) in history" :key="idx" class="mb-3">
        <!-- User message -->
        <div v-if="msg.role === 'user'" class="d-flex justify-content-end">
          <div class="msg-user bg-primary text-white rounded-3 px-3 py-2"
               :class="{ 'opacity-75': msg.error }"
               style="max-width:85%;font-size:0.85rem;white-space:pre-wrap">
            {{ msg.content }}
          </div>
        </div>
        <!-- Assistant message -->
        <div v-else class="d-flex justify-content-start">
          <div class="msg-assistant bg-body-secondary rounded-3 px-3 py-2"
               style="max-width:95%;font-size:0.85rem">
            <div class="msg-markdown" v-html="renderMarkdown(msg.content)"></div>
            <!-- Citation cards -->
            <div v-if="msg.chunks && msg.chunks.length > 0" class="mt-2 pt-2 border-top">
              <div class="text-muted mb-2" style="font-size:0.7rem">
                <i class="bi bi-bookmark me-1"></i>{{ msg.chunks.length }} sumber
              </div>
              <div v-for="(chunk, ci) in msg.chunks" :key="ci"
                   class="citation-card mb-2 p-2 rounded-2 border bg-white"
                   :class="{ 'citation-clickable': hasCitationCallback }"
                   @click="handleCitationClick(chunk)">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                  <span class="filename text-truncate fw-semibold"
                        style="font-size:0.75rem"
                        :title="citationFilename(chunk)">
                    <i class="bi bi-file-earmark-text me-1 text-muted"></i>{{ citationFilename(chunk) }}
                  </span>
                  <span v-if="chunk.score != null" class="badge bg-info-subtle text-info-emphasis flex-shrink-0"
                        style="font-size:0.65rem">{{ formatScore(chunk.score) }}</span>
                </div>
                <div v-if="chunk.heading_path" class="heading-path text-muted mb-1"
                     style="font-size:0.7rem">
                  <i class="bi bi-signpost me-1"></i>{{ chunk.heading_path }}
                </div>
                <div v-if="citationPreview(chunk)" class="content-preview text-muted"
                     style="font-size:0.72rem;line-height:1.35">{{ citationPreview(chunk) }}</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Loading indicator (inline at bottom of list) -->
      <div v-if="loading" class="d-flex justify-content-start mb-3">
        <div class="msg-assistant bg-body-secondary rounded-3 px-3 py-2 text-muted small">
          <div class="spinner-border spinner-border-sm me-1"></div>
          Memproses…
        </div>
      </div>
    </div>

    <!-- Error alert + retry -->
    <div v-if="errorMsg" class="px-3 pb-2 flex-shrink-0">
      <div class="alert alert-danger alert-dismissible mb-0 py-2 small">
        <button type="button" class="btn-close btn-close-sm" @click="errorMsg = ''"></button>
        <i class="bi bi-exclamation-triangle me-1"></i>{{ errorMsg }}
        <button v-if="canRetry" class="btn btn-sm btn-outline-danger ms-2 py-0"
                @click="retryLastMessage" style="font-size:0.75rem">
          <i class="bi bi-arrow-clockwise me-1"></i>Coba lagi
        </button>
      </div>
    </div>

    <!-- Footer: input + send -->
    <div class="px-3 py-2 border-top flex-shrink-0 bg-light">
      <div class="input-group input-group-sm">
        <input ref="inputBox" type="text" class="form-control" v-model="currentInput"
               :placeholder="placeholder" :disabled="loading"
               @keydown.enter="sendMessage">
        <button class="btn btn-primary" :disabled="loading || !currentInput.trim()"
                @click="sendMessage">
          <i class="bi bi-send"></i>
        </button>
      </div>
    </div>

  </div>
</template>

<script>
module.exports = {
  name: 'cube-chat-rag',
  props: {
    endpoint: { type: String, default: '' },
    cmd: { type: String, default: 'query' },
    placeholder: { type: String, default: 'Tanya tentang dokumen…' },
    greeting: { type: String, default: '' },
    persistKey: { type: String, default: null },
    suggestedQueries: { type: Array, default: function() { return []; } },
    context: { type: Object, default: function() { return {}; } },
    onCitationClick: { type: Function, default: null }
  },
  data() {
    return {
      history: [],
      currentInput: '',
      loading: false,
      errorMsg: '',
      // Merged context (base prop + runtime overrides via setQuery)
      runtimeContext: {},
      // Persistence — set in created() if persistKey resolvable
      persistEnabled: false,
      saveTimer: null
    }
  },
  computed: {
    canRetry() {
      if (this.history.length === 0) return false;
      var last = this.history[this.history.length - 1];
      return last && last.role === 'user' && last.error;
    },
    mergedContext() {
      return Object.assign({}, this.context, this.runtimeContext);
    },
    hasCitationCallback() {
      return typeof this.onCitationClick === 'function';
    }
  },
  methods: {
    sendMessage() {
      var query = (this.currentInput || '').trim();
      if (!query || this.loading) return;
      this.currentInput = '';
      this.pushUserMessage(query);
      this.dispatchQuery(query);
    },
    pushUserMessage(query) {
      this.history.push({
        role: 'user',
        content: query,
        timestamp: new Date().toISOString(),
        error: false
      });
      this.scrollToBottom();
    },
    dispatchQuery(query) {
      if (!this.endpoint) {
        this.errorMsg = 'Endpoint chat belum dikonfigurasi.';
        this.markLastUserError();
        return;
      }
      this.loading = true;
      this.errorMsg = '';
      var self = this;
      axios.post(this.endpoint, {
        cmd: this.cmd,
        query: query,
        context: this.mergedContext
      }).then(function(resp) {
        var data = resp.data || {};
        self.history.push({
          role: 'assistant',
          content: data.answer || '',
          chunks: data.chunks || [],
          metadata: data.metadata || {},
          timestamp: new Date().toISOString()
        });
        self.scrollToBottom();
      }).catch(function(e) {
        self.errorMsg = self.formatError(e);
        self.markLastUserError();
      }).then(function() {
        self.loading = false;
      });
    },
    markLastUserError() {
      var last = this.history[this.history.length - 1];
      if (last && last.role === 'user') {
        last.error = true;
      }
    },
    retryLastMessage() {
      if (!this.canRetry) return;
      var last = this.history[this.history.length - 1];
      last.error = false;
      this.dispatchQuery(last.content);
    },
    clearHistory() {
      this.history = [];
      this.errorMsg = '';
      this.removePersisted();
    },
    // ----- Persistence -----
    storageAvailable() {
      try {
        var k = '__chatrag_probe__';
        window.localStorage.setItem(k, '1');
        window.localStorage.removeItem(k);
        return true;
      } catch (e) {
        return false;
      }
    },
    loadPersisted() {
      if (!this.persistKey || !this.persistEnabled) return;
      var raw;
      try {
        raw = window.localStorage.getItem(this.persistKey);
      } catch (e) {
        return;
      }
      if (!raw) return;
      try {
        var parsed = JSON.parse(raw);
        if (parsed && Array.isArray(parsed.history)) {
          this.history = this.enforceMaxHistory(parsed.history);
        }
      } catch (e) {
        // Corrupt data — wipe and warn
        try { window.localStorage.removeItem(this.persistKey); } catch (_) {}
        console.warn('[cube-chat-rag] localStorage corrupt, reset key', this.persistKey);
      }
    },
    schedulePersist() {
      if (!this.persistKey || !this.persistEnabled) return;
      if (this.saveTimer) clearTimeout(this.saveTimer);
      var self = this;
      this.saveTimer = setTimeout(function() { self.flushPersist(); }, 300);
    },
    flushPersist() {
      if (!this.persistKey || !this.persistEnabled) return;
      try {
        var payload = JSON.stringify({
          history: this.history,
          updated_at: new Date().toISOString()
        });
        window.localStorage.setItem(this.persistKey, payload);
      } catch (e) {
        // Quota exceeded or write error — disable persistence, keep in-memory
        this.persistEnabled = false;
        console.warn('[cube-chat-rag] localStorage write failed, persist disabled', e.message);
      }
    },
    removePersisted() {
      if (!this.persistKey) return;
      try {
        window.localStorage.removeItem(this.persistKey);
      } catch (e) { /* ignore */ }
    },
    enforceMaxHistory(arr) {
      var MAX = 50;
      if (!Array.isArray(arr)) return [];
      if (arr.length <= MAX) return arr;
      return arr.slice(arr.length - MAX);
    },
    pickSuggested(query) {
      if (this.loading) return;
      this.currentInput = '';
      this.pushUserMessage(query);
      this.dispatchQuery(query);
    },
    handleCitationClick(chunk) {
      if (typeof this.onCitationClick === 'function') {
        this.onCitationClick(chunk);
      }
    },
    // ----- Citation field accessors (forgiving of naming variants) -----
    citationFilename(chunk) {
      return chunk.file
          || chunk.doc_filename
          || chunk.filename
          || ('chunk #' + (chunk.chunk_id != null ? chunk.chunk_id : chunk.chunk_index));
    },
    citationPreview(chunk) {
      var raw = chunk.content_preview || chunk.content || '';
      if (!raw) return '';
      // Truncate to ~3 lines
      var max = 220;
      return raw.length > max ? raw.slice(0, max).trimEnd() + '…' : raw;
    },
    formatScore(score) {
      if (score == null || isNaN(score)) return '';
      var n = parseFloat(score);
      // Score in [0,1] -> percent, else show as-is rounded
      return n <= 1 ? Math.round(n * 100) + '%' : n.toFixed(2);
    },
    // ----- Markdown rendering (XSS-safe via DOMPurify) -----
    renderMarkdown(content) {
      if (!content) return '';
      var src = String(content);
      try {
        if (typeof marked !== 'undefined' && typeof DOMPurify !== 'undefined') {
          var html = marked.parse(src, { breaks: true, gfm: true });
          return DOMPurify.sanitize(html);
        }
      } catch (e) {
        console.warn('[cube-chat-rag] markdown render failed', e.message);
      }
      // Fallback: escape + preserve line breaks
      return this.escapeHtml(src).replace(/\n/g, '<br>');
    },
    escapeHtml(str) {
      var div = document.createElement('div');
      div.appendChild(document.createTextNode(str));
      return div.innerHTML;
    },
    formatError(e) {
      if (e.response && e.response.data) {
        var d = e.response.data;
        if (typeof d === 'object' && d.notification) return d.notification;
        if (typeof d === 'object' && d.error) return d.error;
        if (typeof d === 'string') return d;
      }
      return e.message || 'Terjadi kesalahan saat memproses pertanyaan.';
    },
    scrollToBottom() {
      var self = this;
      this.$nextTick(function() {
        var el = self.$refs.messageList;
        if (el) el.scrollTop = el.scrollHeight;
      });
    },
    focusInput() {
      var self = this;
      this.$nextTick(function() {
        var el = self.$refs.inputBox;
        if (el) el.focus();
      });
    },
    // Public API for external trigger (e.g., cross-link from coverage table)
    setQuery(query, ctxOverrides) {
      if (ctxOverrides && typeof ctxOverrides === 'object') {
        this.runtimeContext = Object.assign({}, ctxOverrides);
      }
      if (query) {
        this.currentInput = query;
        this.sendMessage();
      }
    }
  },
  watch: {
    history: {
      deep: true,
      handler: function(newVal) {
        // LRU enforcement — if exceeds max, trim oldest
        if (newVal.length > 50) {
          this.history = newVal.slice(newVal.length - 50);
          return; // watcher will re-fire with trimmed array
        }
        this.schedulePersist();
      }
    }
  },
  created() {
    this.persistEnabled = !!this.persistKey && this.storageAvailable();
    this.loadPersisted();
  },
  mounted() {
    var self = this;
    // Expose global helper for cross-link / external trigger
    window.cubeChat = {
      setQuery: function(query, ctx) { self.setQuery(query, ctx); },
      clear: function() { self.clearHistory(); },
      open: function(query, ctx) {
        if (typeof window.openSidePanel === 'function') {
          window.openSidePanel('chat');
        }
        if (query) {
          self.$nextTick(function() { self.setQuery(query, ctx); });
        }
      }
    };
    // Auto-focus input when chat panel opens
    var offcanvas = document.getElementById('sidePanelOffcanvas');
    if (offcanvas) {
      offcanvas.addEventListener('shown.bs.offcanvas', function() {
        var chatPanel = document.getElementById('sidePanel-chat');
        if (chatPanel && chatPanel.style.display !== 'none') {
          self.focusInput();
          self.scrollToBottom();
        }
      });
    }
  }
}
</script>

<style>
/* Component styles — namespaced via .cube-chat-rag wrapper.
   Non-scoped because v-html markdown children need style penetration,
   and httpVueLoader scoped CSS does not support deep selectors. */
.cube-chat-rag .msg-user,
.cube-chat-rag .msg-assistant {
  word-wrap: break-word;
  overflow-wrap: break-word;
}
.cube-chat-rag .citation-card {
  transition: background-color 0.12s, border-color 0.12s;
}
.cube-chat-rag .citation-clickable {
  cursor: pointer;
}
.cube-chat-rag .citation-clickable:hover {
  background-color: #f0f6ff !important;
  border-color: #b6d4fe !important;
}
.cube-chat-rag .msg-markdown p:last-child { margin-bottom: 0; }
.cube-chat-rag .msg-markdown ul,
.cube-chat-rag .msg-markdown ol { padding-left: 1.25rem; margin-bottom: 0.5rem; }
.cube-chat-rag .msg-markdown code {
  background: rgba(0,0,0,0.06);
  padding: 0.1rem 0.25rem;
  border-radius: 3px;
  font-size: 0.8rem;
}
.cube-chat-rag .msg-markdown pre {
  background: rgba(0,0,0,0.06);
  padding: 0.5rem;
  border-radius: 4px;
  overflow-x: auto;
}
.cube-chat-rag .msg-markdown a { color: #0d6efd; }
</style>
