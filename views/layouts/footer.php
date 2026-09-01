    <footer class="footer mt-auto" style="background:linear-gradient(180deg,#1a1a2e,#0f0f1a);border-top:1px solid rgba(25,135,84,0.2)">
        <div class="container-fluid px-4 pt-4 pb-3">
            <div class="text-center">
                <!-- Logo & Name -->
                <div class="mb-2">
                    <span style="font-size:2rem">🌿</span>
                </div>
                <h6 class="text-white fw-bold mb-1" style="letter-spacing:2px">HAZINA ASILI</h6>
                <p class="mb-3" style="font-size:.8rem;color:rgba(255,255,255,.45)">Natural Organic Compounds Database</p>

                <!-- Quick Links -->
                <?php if (isLoggedIn()): ?>
                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <a href="<?= BASE_URL ?>views/researcher/dashboard.php" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:.75rem">
                        <i class="bi bi-speedometer2 me-1"></i>Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>views/researcher/compounds/index.php" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:.75rem">
                        <i class="bi bi-capsule me-1"></i>Compounds
                    </a>
                    <a href="<?= BASE_URL ?>views/researcher/organisms/index.php" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:.75rem">
                        <i class="bi bi-tree me-1"></i>Organisms
                    </a>
                    <a href="<?= BASE_URL ?>views/profile.php" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:.75rem">
                        <i class="bi bi-person me-1"></i>Profile
                    </a>
                </div>
                <?php else: ?>
                <div class="d-flex flex-wrap justify-content-center gap-2 mb-3">
                    <a href="<?= BASE_URL ?>views/auth/login.php" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:.75rem">
                        <i class="bi bi-box-arrow-in-right me-1"></i>Login
                    </a>
                    <a href="<?= BASE_URL ?>views/auth/register.php" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:.75rem">
                        <i class="bi bi-person-plus me-1"></i>Register
                    </a>
                </div>
                <?php endif; ?>

                <!-- Green divider line -->
                <div class="mx-auto mb-3" style="width:80px;height:2px;background:linear-gradient(90deg,transparent,#198754,transparent);border-radius:2px"></div>

                <!-- Credits -->
                <div style="font-size:.75rem;color:rgba(255,255,255,.4)">
                    &copy; <?= date('Y') ?> HAZINA ASILI &middot; v<?= APP_VERSION ?> &middot;
                    Developed by
                    <span class="fw-semibold" style="color:#4ade80">Machibya</span> &amp;
                    <span class="fw-semibold" style="color:#60a5fa">Songelael</span>
                </div>
                <div class="mt-1" style="font-size:.7rem;color:rgba(255,255,255,.3)">
                    <kbd style="font-size:.6rem;padding:1px 5px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);border-radius:3px">?</kbd>
                    <span class="ms-1">Keyboard shortcuts</span>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top button -->
    <button id="backToTop" title="Back to top" aria-label="Scroll to top">
        <i class="bi bi-chevron-up"></i>
    </button>

    <!-- AI Chat Widget -->
    <?php if (isLoggedIn() && AI_ENABLED): ?>
    <div id="aiChatWidget">
        <button id="aiChatToggle" class="ai-chat-btn" title="AI Assistant" aria-label="Open AI Assistant">
            <i class="bi bi-robot"></i>
        </button>
        <div id="aiChatPanel" class="ai-chat-panel" style="display:none">
            <div class="ai-chat-header">
                <span><i class="bi bi-robot me-2"></i>AI Research Assistant</span>
                <button id="aiChatClose" class="btn btn-sm btn-link text-white"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="aiChatMessages" class="ai-chat-messages">
                <div class="ai-msg ai-msg-bot">
                    <strong>Hi!</strong> I'm your research assistant. Ask me about:
                    <ul class="mb-0 mt-1 small">
                        <li>Compound properties & activities</li>
                        <li>Research suggestions</li>
                        <li>Structure-activity relationships</li>
                        <li>Any natural products question</li>
                    </ul>
                </div>
            </div>
            <div class="ai-chat-input">
                <form id="aiChatForm" autocomplete="off">
                    <div class="input-group">
                        <input type="text" id="aiChatInput" class="form-control form-control-sm"
                               placeholder="Ask about compounds..." maxlength="500" required>
                        <button type="submit" class="btn btn-success btn-sm" id="aiSendBtn">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom JS -->
    <script src="<?= BASE_URL ?>assets/js/app.js"></script>

    <?php if (isLoggedIn() && AI_ENABLED): ?>
    <script>
    (function() {
        var toggle = document.getElementById('aiChatToggle');
        var panel = document.getElementById('aiChatPanel');
        var close = document.getElementById('aiChatClose');
        var form = document.getElementById('aiChatForm');
        var input = document.getElementById('aiChatInput');
        var messages = document.getElementById('aiChatMessages');
        var sendBtn = document.getElementById('aiSendBtn');
        var BASE = document.querySelector('meta[name="base-url"]')?.content || '/DB/project/';

        toggle.addEventListener('click', function() {
            var open = panel.style.display === 'none';
            panel.style.display = open ? 'flex' : 'none';
            toggle.style.display = open ? 'none' : 'flex';
            if (open) input.focus();
        });

        close.addEventListener('click', function() {
            panel.style.display = 'none';
            toggle.style.display = 'flex';
        });

        function addMessage(text, isUser) {
            var div = document.createElement('div');
            div.className = 'ai-msg ' + (isUser ? 'ai-msg-user' : 'ai-msg-bot');
            div.innerHTML = text.replace(/\n/g, '<br>');
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
        }

        function formatResponse(text) {
            // Basic markdown-like formatting
            text = text.replace(/## (.*)/g, '<strong class="d-block mt-2">$1</strong>');
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
            text = text.replace(/`(.*?)`/g, '<code>$1</code>');
            return text;
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var question = input.value.trim();
            if (!question) return;

            addMessage(question, true);
            input.value = '';
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';

            var formData = new FormData();
            formData.append('ai_action', 'ask');
            formData.append('question', question);

            fetch(BASE + 'controllers/api_ai.php', {
                method: 'POST',
                body: formData
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.success) {
                    addMessage(formatResponse(data.answer), false);
                } else {
                    addMessage('<span class="text-danger"><i class="bi bi-exclamation-circle"></i> ' + (data.error || 'Error getting response.') + '</span>', false);
                }
            })
            .catch(function() {
                addMessage('<span class="text-danger">Network error. Please try again.</span>', false);
            })
            .finally(function() {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="bi bi-send"></i>';
            });
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
