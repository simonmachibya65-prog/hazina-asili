/**
 * HAZINA ASILI — Application JavaScript v3.0
 * Features: Dark mode, keyboard shortcuts, AJAX search, loading states, accessibility
 */
document.addEventListener('DOMContentLoaded', function () {

    // ══════════════════════════════════════════════════════════════
    // DARK MODE
    // ══════════════════════════════════════════════════════════════

    const ThemeManager = {
        init: function() {
            const saved = localStorage.getItem('theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = saved || (prefersDark ? 'dark' : 'light');
            this.apply(theme);

            // Listen for OS preference changes
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (!localStorage.getItem('theme')) {
                    ThemeManager.apply(e.matches ? 'dark' : 'light');
                }
            });
        },

        apply: function(theme) {
            document.documentElement.setAttribute('data-bs-theme', theme);
            this.updateIcons(theme);
        },

        toggle: function() {
            const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
            const next = current === 'dark' ? 'light' : 'dark';
            this.apply(next);
            localStorage.setItem('theme', next);
        },

        updateIcons: function(theme) {
            document.querySelectorAll('.theme-toggle').forEach(function(btn) {
                const darkIcon = btn.querySelector('.theme-icon-dark');
                const lightIcon = btn.querySelector('.theme-icon-light');
                if (darkIcon) darkIcon.style.display = theme === 'dark' ? 'none' : '';
                if (lightIcon) lightIcon.style.display = theme === 'light' ? 'none' : '';
            });
        }
    };

    ThemeManager.init();

    // Expose toggle globally
    window.toggleDarkMode = function() { ThemeManager.toggle(); };

    // ══════════════════════════════════════════════════════════════
    // KEYBOARD SHORTCUTS
    // ══════════════════════════════════════════════════════════════

    const Shortcuts = {
        init: function() {
            document.addEventListener('keydown', function(e) {
                // Don't trigger in form inputs
                if (e.target.matches('input, textarea, select, [contenteditable]')) {
                    // Escape in search field clears it
                    if (e.key === 'Escape' && e.target.matches('input[name="search"]')) {
                        e.target.value = '';
                        e.target.form.submit();
                    }
                    return;
                }

                // Ctrl/Cmd + K → Focus search
                if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                    e.preventDefault();
                    var search = document.querySelector('input[name="search"]');
                    if (search) search.focus();
                }

                // Alt + D → Dashboard
                if (e.altKey && e.key === 'd') {
                    e.preventDefault();
                    var dashLink = document.querySelector('a[href*="dashboard"]');
                    if (dashLink) window.location.href = dashLink.href;
                }

                // Alt + N → New/Create (first add button)
                if (e.altKey && e.key === 'n') {
                    e.preventDefault();
                    var createLink = document.querySelector('a[href*="create"]');
                    if (createLink) window.location.href = createLink.href;
                }

                // Alt + T → Toggle dark mode
                if (e.altKey && e.key === 't') {
                    e.preventDefault();
                    ThemeManager.toggle();
                }

                // ? → Show shortcut help
                if (e.key === '?' && !e.ctrlKey && !e.altKey) {
                    e.preventDefault();
                    Shortcuts.showHelp();
                }
            });
        },

        showHelp: function() {
            var existing = document.getElementById('shortcutModal');
            if (existing) { bootstrap.Modal.getInstance(existing)?.show() || new bootstrap.Modal(existing).show(); return; }

            var modal = document.createElement('div');
            modal.id = 'shortcutModal';
            modal.className = 'modal fade';
            modal.innerHTML = '<div class="modal-dialog"><div class="modal-content"><div class="modal-header">' +
                '<h5 class="modal-title"><i class="bi bi-keyboard"></i> Keyboard Shortcuts</h5>' +
                '<button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>' +
                '<div class="modal-body"><table class="table table-sm mb-0"><tbody>' +
                '<tr><td><kbd>Ctrl+K</kbd></td><td>Focus search</td></tr>' +
                '<tr><td><kbd>Alt+D</kbd></td><td>Go to Dashboard</td></tr>' +
                '<tr><td><kbd>Alt+N</kbd></td><td>Create new item</td></tr>' +
                '<tr><td><kbd>Alt+T</kbd></td><td>Toggle dark mode</td></tr>' +
                '<tr><td><kbd>Esc</kbd></td><td>Clear search / Close modal</td></tr>' +
                '<tr><td><kbd>?</kbd></td><td>Show this help</td></tr>' +
                '</tbody></table></div></div></div>';
            document.body.appendChild(modal);
            new bootstrap.Modal(modal).show();
        }
    };

    Shortcuts.init();

    // ══════════════════════════════════════════════════════════════
    // AJAX SEARCH WITH DEBOUNCING
    // ══════════════════════════════════════════════════════════════

    const BASE_URL = document.querySelector('meta[name="base-url"]')?.content || '/DB/project/';

    document.querySelectorAll('input[name="search"]').forEach(function(input) {
        var dropdown = null;
        var debounceTimer = null;
        var selectedIndex = -1;

        function createDropdown() {
            if (dropdown) return dropdown;
            dropdown = document.createElement('div');
            dropdown.className = 'list-group position-absolute w-100 shadow-lg';
            dropdown.style.cssText = 'z-index:1050;max-height:300px;overflow-y:auto;top:100%;border-radius:.5rem';
            dropdown.setAttribute('role', 'listbox');
            input.parentElement.style.position = 'relative';
            input.parentElement.appendChild(dropdown);
            return dropdown;
        }

        function search(q) {
            fetch(BASE_URL + 'controllers/api_search.php?q=' + encodeURIComponent(q))
                .then(function(r) { return r.json(); })
                .then(function(data) {
                    var dd = createDropdown();
                    selectedIndex = -1;
                    if (!data.length) {
                        dd.innerHTML = '<div class="list-group-item text-muted small py-3 text-center"><i class="bi bi-search me-1"></i>No results for "' + q + '"</div>';
                        return;
                    }
                    dd.innerHTML = data.map(function(item, i) {
                        return '<a href="' + item.url + '" class="list-group-item list-group-item-action py-2" role="option" data-index="' + i + '">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                            '<span class="fw-semibold">' + item.name + '</span>' +
                            '<span class="badge bg-' + item.color + '">' + item.type + '</span>' +
                            '</div>' +
                            (item.detail ? '<small class="text-muted">' + item.detail + '</small>' : '') +
                            '</a>';
                    }).join('');
                }).catch(function() {});
        }

        input.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            var q = this.value.trim();
            if (q.length < 2) { if (dropdown) dropdown.innerHTML = ''; return; }
            debounceTimer = setTimeout(function() { search(q); }, 300);
        });

        // Keyboard navigation in dropdown
        input.addEventListener('keydown', function(e) {
            if (!dropdown || !dropdown.children.length) return;
            var items = dropdown.querySelectorAll('[role="option"]');
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                items.forEach(function(el, i) { el.classList.toggle('active', i === selectedIndex); });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, 0);
                items.forEach(function(el, i) { el.classList.toggle('active', i === selectedIndex); });
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items[selectedIndex]?.click();
            }
        });

        input.addEventListener('blur', function() {
            setTimeout(function() { if (dropdown) dropdown.innerHTML = ''; }, 200);
        });
    });

    // ══════════════════════════════════════════════════════════════
    // PASSWORD FEATURES
    // ══════════════════════════════════════════════════════════════

    // Password visibility toggle
    document.querySelectorAll('.toggle-password').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var input = document.getElementById(this.dataset.target);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            var icon = this.querySelector('i');
            if (icon) {
                icon.classList.toggle('bi-eye',       !show);
                icon.classList.toggle('bi-eye-slash',  show);
            }
        });
    });

    // Password match (register)
    var pw  = document.getElementById('regPassword');
    var pw2 = document.getElementById('regConfirm');
    if (pw && pw2) {
        function checkMatch() {
            pw2.setCustomValidity(
                pw2.value && pw.value !== pw2.value ? 'Passwords do not match.' : ''
            );
        }
        pw.addEventListener('input', checkMatch);
        pw2.addEventListener('input', checkMatch);
    }

    // Password strength bar
    var regPw = document.getElementById('regPassword');
    if (regPw) {
        var wrap = regPw.closest('.input-group') || regPw.parentElement;
        if (wrap) {
            var bar = document.createElement('div');
            bar.className = 'progress mt-1';
            bar.style.cssText = 'height:4px;border-radius:2px';
            bar.innerHTML = '<div id="strengthFill" class="progress-bar" style="width:0;transition:width .3s"></div>';
            wrap.insertAdjacentElement('afterend', bar);

            var strengthText = document.createElement('div');
            strengthText.className = 'form-text mt-0';
            strengthText.id = 'strengthText';
            bar.insertAdjacentElement('afterend', strengthText);

            regPw.addEventListener('input', function () {
                var v = this.value;
                var s = 0;
                if (v.length >= 8)           s++;
                if (/[A-Z]/.test(v))         s++;
                if (/[0-9]/.test(v))         s++;
                if (/[^A-Za-z0-9]/.test(v))  s++;
                var fill   = document.getElementById('strengthFill');
                var colors = ['bg-danger','bg-warning','bg-info','bg-success'];
                var labels = ['Weak','Fair','Good','Strong'];
                fill.className   = 'progress-bar ' + (colors[s-1] || 'bg-danger');
                fill.style.width = (s * 25) + '%';
                document.getElementById('strengthText').textContent = v.length > 0 ? 'Strength: ' + (labels[s-1] || 'Too short') : '';
            });
        }
    }

    // ══════════════════════════════════════════════════════════════
    // UI ENHANCEMENTS
    // ══════════════════════════════════════════════════════════════

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.alert.alert-dismissible').forEach(function (el) {
        setTimeout(function () {
            try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch(e) {}
        }, 5000);
    });

    // Bootstrap form validation
    document.querySelectorAll('form[novalidate]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });

    // Confirm delete (data-confirm attribute)
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });

    // Loading overlay on form submit
    var overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function () {
                if (form.checkValidity !== undefined && form.checkValidity()) {
                    overlay.classList.add('active');
                }
            });
        });
    }

    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover' });
    });

    // Notification bell pulse on unread
    var bell = document.querySelector('.bi-bell');
    if (bell) {
        var badge = bell.closest('.nav-item')?.querySelector('.badge');
        if (badge && parseInt(badge.textContent) > 0) {
            bell.classList.add('bell-pulse');
        }
    }

    // Table row click → view link
    document.querySelectorAll('tr[data-href]').forEach(function (row) {
        row.style.cursor = 'pointer';
        row.addEventListener('click', function (e) {
            if (!e.target.closest('button, a, form, input')) {
                window.location.href = this.dataset.href;
            }
        });
    });

    // Confirm before dangerous submits
    document.querySelectorAll('[data-confirm-submit]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirmSubmit)) e.preventDefault();
        });
    });

    // Auto-submit on select change
    document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
        sel.addEventListener('change', function () { this.form.submit(); });
    });

    // Smooth scroll to flash message
    var flash = document.querySelector('.alert:not(.role-info)');
    if (flash) {
        flash.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    // Copy to clipboard
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            navigator.clipboard.writeText(this.dataset.copy).then(function () {
                btn.innerHTML = '<i class="bi bi-check"></i>';
                setTimeout(function () {
                    btn.innerHTML = '<i class="bi bi-clipboard"></i>';
                }, 1500);
            });
        });
    });

    // ══════════════════════════════════════════════════════════════
    // MOLECULAR WEIGHT AUTO-CALCULATOR
    // ══════════════════════════════════════════════════════════════

    var formulaInput = document.querySelector('input[name="formula"]');
    var mwInput = document.querySelector('input[name="molecular_weight"]');
    if (formulaInput && mwInput) {
        var atomicWeights = {
            H:1.008,He:4.003,Li:6.941,Be:9.012,B:10.81,C:12.011,N:14.007,O:15.999,
            F:18.998,Ne:20.18,Na:22.99,Mg:24.305,Al:26.982,Si:28.086,P:30.974,
            S:32.06,Cl:35.45,Ar:39.948,K:39.098,Ca:40.078,Fe:55.845,Co:58.933,
            Ni:58.693,Cu:63.546,Zn:65.38,Br:79.904,I:126.904,Se:78.971,Mn:54.938
        };

        function estimateMW(formula) {
            var regex = /([A-Z][a-z]?)(\d*)/g;
            var mw = 0, match;
            while ((match = regex.exec(formula)) !== null) {
                if (!match[1]) continue;
                var el = match[1];
                var cnt = match[2] ? parseInt(match[2]) : 1;
                if (atomicWeights[el]) mw += atomicWeights[el] * cnt;
                else return null;
            }
            return mw > 0 ? mw : null;
        }

        var mwHint = document.createElement('div');
        mwHint.className = 'form-text';
        mwHint.id = 'mwEstimate';
        mwInput.parentElement.appendChild(mwHint);

        formulaInput.addEventListener('input', function() {
            var formula = this.value.trim();
            if (!formula) { mwHint.innerHTML = ''; return; }
            if (!/^([A-Z][a-z]?\d*)+$/.test(formula)) {
                mwHint.innerHTML = '<span class="text-danger"><i class="bi bi-x-circle"></i> Invalid formula format</span>';
                return;
            }
            var estimated = estimateMW(formula);
            if (estimated) {
                mwHint.innerHTML = '<span class="text-success"><i class="bi bi-calculator"></i> Estimated: <strong>' +
                    estimated.toFixed(4) + ' g/mol</strong></span>' +
                    ' <button type="button" class="btn btn-sm btn-outline-success ms-2 py-0 px-1" id="autoFillMW">Use this</button>';
                document.getElementById('autoFillMW')?.addEventListener('click', function() {
                    mwInput.value = estimated.toFixed(4);
                    mwHint.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Auto-filled!</span>';
                });
            } else {
                mwHint.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> Unknown element in formula</span>';
            }
        });

        mwInput.addEventListener('input', function() {
            var formula = formulaInput.value.trim();
            var entered = parseFloat(this.value);
            if (!formula || !entered || isNaN(entered)) return;
            var estimated = estimateMW(formula);
            if (estimated) {
                var diff = Math.abs(entered - estimated) / estimated;
                if (diff > 0.2) {
                    mwHint.innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> MW differs >20% from estimated (' + estimated.toFixed(2) + ')</span>';
                } else {
                    mwHint.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> MW matches formula</span>';
                }
            }
        });
    }

    // ══════════════════════════════════════════════════════════════
    // BACK TO TOP BUTTON
    // ══════════════════════════════════════════════════════════════

    var backToTop = document.getElementById('backToTop');
    if (backToTop) {
        window.addEventListener('scroll', function() {
            backToTop.classList.toggle('visible', window.scrollY > 300);
        });
        backToTop.addEventListener('click', function() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

    // ══════════════════════════════════════════════════════════════
    // ACCESSIBILITY
    // ══════════════════════════════════════════════════════════════

    // Add skip navigation link
    var skip = document.createElement('a');
    skip.href = '#main-content';
    skip.className = 'visually-hidden-focusable position-absolute top-0 start-0 p-2 bg-primary text-white';
    skip.textContent = 'Skip to main content';
    skip.style.zIndex = '10000';
    document.body.prepend(skip);

    // Mark main content
    var main = document.querySelector('main');
    if (main && !main.id) main.id = 'main-content';

    // Focus management for modals
    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('shown.bs.modal', function() {
            var firstInput = modal.querySelector('input, button:not(.btn-close), select, textarea');
            if (firstInput) firstInput.focus();
        });
    });

});
