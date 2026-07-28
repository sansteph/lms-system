@php
    $hideAiChatbot = request()->routeIs(
        'content.preview*',
        'content.file*',
        'assessment.*',
        'assessment-results.*',
        'admin.assessment*',
        'teacher.assessments*',
        'student.assessment*',
        'assessment.paper',
        'assessment.answer.file',
        'student.assessment.take',
        'student.content.ai-review.quiz',
        'teacher.ai-prep.quiz',
        'portal',
        'admin.login',
        'teacher.login',
        'student.login',
        'independent.register',
        'independent.login',
        'coming.soon',
        'admin.institute.register',
        'independent.courses.learn'
    );

@endphp

@unless($hideAiChatbot)
    <div class="ai-chatbot-panel" id="aiChatbotPanel" aria-live="polite">
        <div class="ai-chatbot-header">
            <div>
                <strong>InnovatEdge Assistant</strong>
                <span>Only LMS workflow and available lesson content.</span>
            </div>
            <button type="button" class="ai-chatbot-close" id="aiChatbotClose" aria-label="Close assistant">
                &times;
            </button>
        </div>

        <div class="ai-chatbot-messages" id="aiChatbotMessages">
            <div class="ai-chatbot-message bot">
                Hi, I can help only with InnovatEdge LMS workflows and the lesson content available to you.
            </div>
        </div>

        <form class="ai-chatbot-form" id="aiChatbotForm">
            <input type="text"
                   id="aiChatbotInput"
                   maxlength="1200"
                   autocomplete="off"
                   placeholder="Ask a question..."
                   aria-label="Ask the AI assistant">
            <button type="submit" aria-label="Send question">
                <i class="fa fa-paper-plane"></i>
            </button>
        </form>
    </div>

    <button type="button"
            class="ai-chatbot-launcher"
            id="aiChatbotLauncher"
            aria-label="Open AI assistant">
        <span class="ai-chatbot-launcher-glow" aria-hidden="true"></span>
        <span class="ai-chatbot-launcher-icon" aria-hidden="true">
            <img src="{{ asset('images/cupbot-chatbot-icon.png') }}" alt="">
        </span>
    </button>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const panel = document.getElementById('aiChatbotPanel');
            const launcher = document.getElementById('aiChatbotLauncher');
            const closeButton = document.getElementById('aiChatbotClose');
            const form = document.getElementById('aiChatbotForm');
            const input = document.getElementById('aiChatbotInput');
            const messages = document.getElementById('aiChatbotMessages');
            const animatedAssistants = Array.from(document.querySelectorAll('.ai-chatbot-trigger, #floatingAiAssistant, #heroAiAssistant'));

            if (!panel || !launcher || !closeButton || !form || !input || !messages) {
                return;
            }

            const openPanel = function () {
                panel.classList.add('open');
                input.focus();
            };

            const closePanel = function () {
                panel.classList.remove('open');
            };

            const addMessage = function (text, type) {
                const item = document.createElement('div');
                item.className = 'ai-chatbot-message ' + type;
                item.textContent = text;
                messages.appendChild(item);
                messages.scrollTop = messages.scrollHeight;
                return item;
            };

            launcher.addEventListener('click', openPanel);
            closeButton.addEventListener('click', closePanel);

            if (animatedAssistants.length) {
                launcher.classList.add('ai-chatbot-launcher-hidden');
                animatedAssistants.forEach(function (assistant) {
                    assistant.style.pointerEvents = 'auto';
                    assistant.style.cursor = 'pointer';
                    assistant.setAttribute('role', 'button');
                    assistant.setAttribute('tabindex', '0');
                    assistant.setAttribute('aria-label', 'Open InnovatEdge Assistant');
                    assistant.setAttribute('title', 'Ask InnovatEdge Assistant');
                    assistant.addEventListener('click', openPanel);
                    assistant.addEventListener('keydown', function (event) {
                        if (event.key === 'Enter' || event.key === ' ') {
                            event.preventDefault();
                            openPanel();
                        }
                    });
                });
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                const question = input.value.trim();
                if (!question) {
                    return;
                }

                addMessage(question, 'user');
                input.value = '';

                const loading = addMessage('Thinking...', 'bot loading');

                fetch('{{ route('ai-chat.ask') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify({ message: question }),
                })
                    .then(function (response) {
                        return response.json().then(function (payload) {
                            if (!response.ok) {
                                throw new Error(payload.message || 'AI assistant could not answer right now.');
                            }

                            return payload;
                        });
                    })
                    .then(function (payload) {
                        let text = payload.answer || 'I could not prepare a clear answer for that.';

                        if (payload.sources && payload.sources.length) {
                            text += '\n\nBased on: ' + payload.sources.join(', ');
                        }

                        loading.className = 'ai-chatbot-message bot';
                        loading.textContent = text;
                        messages.scrollTop = messages.scrollHeight;
                    })
                    .catch(function (error) {
                        loading.className = 'ai-chatbot-message bot error';
                        loading.textContent = error.message || 'AI assistant is temporarily unavailable.';
                    });
            });
        });
    </script>
@endunless
