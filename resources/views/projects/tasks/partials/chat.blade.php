<div>
    {{-- ===== رأس القسم ===== --}}
    <div class="chat-section-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="section-icon-box blue">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <h5 class="fw-bold text-dark mb-1" style="font-size: 1.05rem;">النقاش والتواصل</h5>
                    <p class="text-muted mb-0 small">تواصل مع فريق العمل حول تفاصيل هذه المهمة</p>
                </div>
            </div>
            <div class="chat-messages-count">
                <i class="fas fa-message"></i>
                <span>{{ $task->discussions->count() }}</span>
                <small>رسالة</small>
            </div>
        </div>
    </div>

    {{-- ===== نافذة المحادثة ===== --}}
    <div class="chat-window" id="chat-messages-container">
        @forelse($task->discussions as $message)
            @php
                $isMe = $message->user_id === auth()->id();
            @endphp

            <div class="chat-message-row {{ $isMe ? 'sent' : 'received' }}">
                {{-- Avatar للطرف الآخر --}}
                @if(!$isMe)
                    <div class="chat-avatar other">
                        {{ mb_strtoupper(mb_substr($message->user?->name ?? 'م', 0, 1, 'UTF-8')) }}
                    </div>
                @endif

                <div class="chat-message-content">
                    {{-- اسم المرسل والوقت --}}
                    <div class="chat-message-meta {{ $isMe ? 'text-end' : '' }}">
                        <span class="chat-sender-name">{{ $message->user?->name ?? 'مستخدم' }}</span>
                        <span class="chat-time">
                            <i class="far fa-clock"></i>
                            {{ $message->created_at->diffForHumans() }}
                        </span>
                    </div>

                    {{-- فقاعة الرسالة --}}
                    <div class="chat-bubble {{ $isMe ? 'sent' : 'received' }}">
                        <div class="chat-message-text">{!! nl2br(e($message->message)) !!}</div>
                        {{-- سهم الفقاعة --}}
                        <div class="bubble-arrow {{ $isMe ? 'sent' : 'received' }}"></div>
                    </div>
                </div>

                {{-- Avatar الخاص بي --}}
                @if($isMe)
                    <div class="chat-avatar me">
                        {{ mb_strtoupper(mb_substr($message->user?->name ?? 'أ', 0, 1, 'UTF-8')) }}
                    </div>
                @endif
            </div>
        @empty
            <div class="chat-empty-state">
                <div class="chat-empty-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h6>لا توجد رسائل بعد</h6>
                <p>ابدأ النقاش مع فريق العمل حول تفاصيل هذه المهمة</p>
                <div class="chat-empty-hint">
                    <i class="fas fa-lightbulb"></i>

                </div>
            </div>
        @endforelse
    </div>

    {{-- ===== نموذج الإرسال ===== --}}
    @can('task.chat.reply', $task)
        <div class="chat-input-wrapper">
            <form action="{{ $task->project_id ? route('projects.tasks.discussions.store', [$task->project_id, $task->id]) : route('tasks.discussions.store', $task) }}" method="POST"
                id="chat-send-form" class="chat-send-form">
                @csrf
                <div class="chat-input-container">
                    <div class="chat-input-icon">
                        <i class="fas fa-pen-to-square"></i>
                    </div>
                    <textarea name="message" class="chat-input" rows="1" placeholder="اكتب رسالتك أو استفسارك هنا..." id="chat-textarea"></textarea>
                    <div class="chat-input-actions">
                        <span class="chat-hint">
                            <kbd>Ctrl</kbd> + <kbd>Enter</kbd> للإرسال
                        </span>
                        <button class="btn-chat-send" type="submit" title="إرسال الرسالة">
                            <i class="fas fa-paper-plane"></i>
                            <span>إرسال</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    @else
        <div class="chat-locked-state">
            <div class="chat-locked-icon">
                <i class="fas fa-lock"></i>
            </div>
            <div class="chat-locked-text">
                <strong>لا تملك صلاحية الكتابة</strong>
                <span>ليس لديك صلاحية إرسال رسائل في هذا النقاش</span>
            </div>
        </div>
    @endcan
</div>

{{-- ===== الأنماط ===== --}}
<style>
    /* ===============================
       رأس القسم
       =============================== */
    .chat-section-header {
        background: #fff;
        border-radius: 14px;
        padding: 1.15rem 1.35rem;
        border: 1px solid #e2e8f0;
        margin-bottom: 1.15rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    }

    .section-icon-box {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .section-icon-box.blue {
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #3b82f6;
    }

    .chat-messages-count {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        padding: 0.5rem 1rem;
        border-radius: 20px;
        color: #2563eb;
        font-weight: 700;
        font-size: 1.1rem;
    }

    .chat-messages-count i {
        font-size: 0.85rem;
    }

    .chat-messages-count small {
        font-size: 0.75rem;
        font-weight: 600;
        color: #3b82f6;
    }

    /* ===============================
       نافذة المحادثة
       =============================== */
    .chat-window {
        height: 450px;
        overflow-y: auto;
        padding: 1.5rem;
        background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
        border-radius: 16px;
        border: 1px solid #e2e8f0;
        margin-bottom: 1rem;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        scrollbar-width: thin;
        scrollbar-color: #cbd5e1 transparent;
    }

    .chat-window::-webkit-scrollbar {
        width: 6px;
    }

    .chat-window::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-window::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 3px;
    }

    .chat-window::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* ===============================
       صف الرسالة
       =============================== */
    .chat-message-row {
        display: flex;
        align-items: flex-end;
        gap: 0.65rem;
        animation: fadeInMessage 0.35s ease backwards;
    }

    .chat-message-row.sent {
        justify-content: flex-start;
        flex-direction: row-reverse;
    }

    .chat-message-row.received {
        justify-content: flex-end;
        flex-direction: row;
    }

    @keyframes fadeInMessage {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .chat-message-row:nth-child(1) {
        animation-delay: 0.05s;
    }

    .chat-message-row:nth-child(2) {
        animation-delay: 0.1s;
    }

    .chat-message-row:nth-child(3) {
        animation-delay: 0.15s;
    }

    .chat-message-row:nth-child(4) {
        animation-delay: 0.2s;
    }

    .chat-message-row:nth-child(5) {
        animation-delay: 0.25s;
    }

    /* ===============================
       Avatar
       =============================== */
    .chat-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.82rem;
        font-weight: 700;
        flex-shrink: 0;
        border: 2px solid #fff;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        text-transform: uppercase;
    }

    .chat-avatar.me {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
    }

    .chat-avatar.other {
        background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        color: #fff;
    }

    /* ===============================
       محتوى الرسالة
       =============================== */
    .chat-message-content {
        max-width: 70%;
        display: flex;
        flex-direction: column;
    }

    .chat-message-row.sent .chat-message-content {
        align-items: flex-start;
    }

    .chat-message-row.received .chat-message-content {
        align-items: flex-end;
    }

    .chat-message-meta {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-bottom: 0.3rem;
        padding: 0 0.5rem;
    }

    .chat-sender-name {
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
    }

    .chat-time {
        font-size: 0.68rem;
        color: #94a3b8;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
    }

    .chat-time i {
        font-size: 0.6rem;
    }

    /* ===============================
       فقاعة الرسالة
       =============================== */
    .chat-bubble {
        position: relative;
        padding: 0.85rem 1.15rem;
        border-radius: 18px;
        font-size: 0.88rem;
        line-height: 1.65;
        word-wrap: break-word;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s ease;
    }

    .chat-bubble:hover {
        transform: scale(1.01);
    }

    /* فقاعة المرسلة (أزرق) */
    .chat-bubble.sent {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    /* فقاعة المستلمة (أبيض) */
    .chat-bubble.received {
        background: #fff;
        color: #1e293b;
        border: 1px solid #e2e8f0;
        border-bottom-left-radius: 4px;
    }

    .chat-message-text {
        position: relative;
        z-index: 1;
    }

    /* سهم الفقاعة */
    .bubble-arrow {
        position: absolute;
        bottom: 0;
        width: 12px;
        height: 12px;
    }

    .bubble-arrow.sent {
        right: -6px;
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        clip-path: polygon(0 0, 0% 100%, 100% 100%);
    }

    .bubble-arrow.received {
        left: -6px;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-top: none;
        border-right: none;
        clip-path: polygon(100% 0, 0 100%, 100% 100%);
    }

    /* ===============================
       نموذج الإدخال
       =============================== */
    .chat-input-wrapper {
        background: #fff;
        border-radius: 14px;
        border: 1.5px solid #e2e8f0;
        overflow: hidden;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.04);
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .chat-input-wrapper:focus-within {
        border-color: #3b82f6;
        box-shadow: 0 4px 20px rgba(59, 130, 246, 0.12);
    }

    .chat-send-form {
        margin: 0;
    }

    .chat-input-container {
        display: flex;
        align-items: flex-end;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
    }

    .chat-input-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        color: #3b82f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.85rem;
        flex-shrink: 0;
        margin-bottom: 2px;
    }

    .chat-input {
        flex: 1;
        border: none;
        outline: none;
        resize: none;
        font-size: 0.9rem;
        color: #1e293b;
        background: transparent;
        padding: 0.5rem 0;
        min-height: 36px;
        max-height: 120px;
        line-height: 1.5;
        font-family: inherit;
    }

    .chat-input::placeholder {
        color: #94a3b8;
    }

    .chat-input-actions {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        flex-shrink: 0;
    }

    .chat-hint {
        font-size: 0.7rem;
        color: #94a3b8;
        display: none;
    }

    .chat-hint kbd {
        padding: 0.15rem 0.4rem;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
        border-radius: 4px;
        font-size: 0.65rem;
        font-weight: 600;
        color: #64748b;
    }

    @media (min-width: 768px) {
        .chat-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }
    }

    .btn-chat-send {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: #fff;
        border: none;
        padding: 0.55rem 1.25rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        cursor: pointer;
        transition: all 0.25s ease;
        box-shadow: 0 4px 12px rgba(59, 130, 246, 0.25);
    }

    .btn-chat-send:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(59, 130, 246, 0.35);
    }

    .btn-chat-send:active {
        transform: translateY(0);
    }

    .btn-chat-send i {
        font-size: 0.8rem;
        transition: transform 0.25s ease;
    }

    .btn-chat-send:hover i {
        transform: translateX(-3px) translateY(-2px);
    }

    /* ===============================
       حالة عدم وجود رسائل
       =============================== */
    .chat-empty-state {
        flex: 1;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 2rem;
    }

    .chat-empty-icon {
        width: 80px;
        height: 80px;
        margin-bottom: 1.25rem;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: #3b82f6;
        animation: float 3s ease-in-out infinite;
    }

    @keyframes float {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-8px);
        }
    }

    .chat-empty-state h6 {
        font-size: 1.05rem;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 0.35rem;
    }

    .chat-empty-state p {
        font-size: 0.88rem;
        color: #94a3b8;
        margin-bottom: 1.25rem;
    }

    .chat-empty-hint {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1rem;
        background: #fffbeb;
        border: 1px solid #fde68a;
        border-radius: 10px;
        font-size: 0.82rem;
        color: #92400e;
    }

    .chat-empty-hint i {
        color: #f59e0b;
    }

    .chat-empty-hint kbd {
        padding: 0.15rem 0.4rem;
        background: #fff;
        border: 1px solid #fde68a;
        border-radius: 4px;
        font-size: 0.7rem;
        font-weight: 600;
        color: #92400e;
    }

    /* ===============================
       حالة عدم وجود صلاحية
       =============================== */
    .chat-locked-state {
        display: flex;
        align-items: center;
        gap: 1rem;
        padding: 1rem 1.25rem;
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        border: 1.5px solid #e2e8f0;
        border-radius: 14px;
    }

    .chat-locked-icon {
        width: 42px;
        height: 42px;
        border-radius: 11px;
        background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%);
        color: #dc2626;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        flex-shrink: 0;
    }

    .chat-locked-text {
        display: flex;
        flex-direction: column;
        gap: 0.15rem;
    }

    .chat-locked-text strong {
        font-size: 0.88rem;
        color: #1e293b;
    }

    .chat-locked-text span {
        font-size: 0.78rem;
        color: #64748b;
    }

    /* ===============================
       تجاوب
       =============================== */
    @media (max-width: 576px) {
        .chat-message-content {
            max-width: 82%;
        }

        .chat-input-container {
            padding: 0.75rem;
        }

        .chat-avatar {
            width: 30px;
            height: 30px;
            font-size: 0.72rem;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const chatWindow = document.getElementById('chat-messages-container');
        if (chatWindow) {
            // Scroll to bottom
            setTimeout(() => {
                chatWindow.scrollTop = chatWindow.scrollHeight;
            }, 100);
        }

        // Support Ctrl+Enter to submit message
        const textarea = document.getElementById('chat-textarea');
        if (textarea) {
            // Auto-resize textarea
            textarea.addEventListener('input', function () {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 120) + 'px';
            });

            textarea.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                    e.preventDefault();
                    document.getElementById('chat-send-form').submit();
                }
            });
        }
    });
</script>
