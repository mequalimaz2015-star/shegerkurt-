<!-- Chatbot Floating Widget -->
<div id="bloomChatWidget" class="bloom-chat-container">
  <!-- Chat Button Trigger -->
  <button id="chatButton" class="chat-toggle-btn" aria-label="Open support chat">
    <ion-icon name="chatbubbles-outline" class="chat-icon"></ion-icon>
    <ion-icon name="close-outline" class="close-icon" style="display:none;"></ion-icon>
    <span class="unread-badge" style="display:none;">0</span>
  </button>

  <!-- Chat Window Panel -->
  <div id="chatWindow" class="chat-window" style="display:none;">
    <div class="chat-header">
      <div class="h-content">
        <div class="chat-logo-sm">S</div>
        <div class="h-text">
          <h3>Customer Support</h3>
          <p><span class="online-indicator"></span> We usually reply in seconds</p>
        </div>
      </div>
    </div>

    <!-- Step 1: Identity Form -->
    <div id="chatIdentityForm" class="chat-step chat-form">
      <div class="form-intro">
        <h4>Hello! 👋</h4>
        <p>Please enter your details to start chatting with our team.</p>
      </div>
      <input type="text" id="chatName" placeholder="Full Name" required class="chat-input-field">
      <input type="tel" id="chatPhone" placeholder="Phone Number" required class="chat-input-field">
      <select id="chatDept" class="chat-input-field">
        <option value="Restaurant">Restaurant Support</option>
        <option value="Construction">Construction Inquiry</option>
      </select>
      <button id="startChatBtn" class="chat-btn-primary">Start Chatting</button>
    </div>

    <!-- Step 2: Message List -->
    <div id="chatInterface" class="chat-step" style="display:none;">
      <div id="chatMessages" class="chat-messages">
        <div class="msg-bubble system">Welcome! How can we help you today?</div>
      </div>
      <div class="chat-footer">
        <form id="chatForm">
          <input type="text" id="chatInput" placeholder="Write a message..." required class="chat-input-type">
          <button type="submit" class="chat-send-btn"><ion-icon name="send-outline"></ion-icon></button>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
/* Chat Widget Styles */
.bloom-chat-container {
    position: fixed; bottom: 30px; right: 30px; z-index: 10000; font-family: 'Rubik', sans-serif;
}
.chat-toggle-btn {
    width: 65px; height: 65px; border-radius: 50%; background: #ff9d2d; color: #fff;
    border: none; box-shadow: 0 10px 30px rgba(255, 157, 45, 0.4); cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 30px;
    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275); position: relative;
}
.chat-toggle-btn:hover { transform: scale(1.1) rotate(5deg); }
.unread-badge {
    position: absolute; top: 0; right: 0; background: #e74c3c; color: #fff;
    font-size: 10px; padding: 3px 6px; border-radius: 10px; border: 2px solid #fff; font-weight: 700;
}
.chat-window {
    position: absolute; bottom: 85px; right: 0; width: 350px; height: 500px;
    background: #fff; border-radius: 20px; box-shadow: 0 15px 50px rgba(0,0,0,0.15);
    overflow: hidden; display: flex; flex-direction: column; opacity: 0; transform: translateY(20px);
    transition: all 0.4s ease; transform-origin: bottom right; pointer-events: none;
}
.chat-window.active { opacity: 1; transform: translateY(0); pointer-events: auto; }

.chat-header {
    background: linear-gradient(135deg, #1e293b, #334155); color: #fff; padding: 25px 20px;
}
.chat-header h3 { margin: 0; font-size: 18px; font-weight: 600; letter-spacing: 0.5px; }
.chat-header p { margin: 5px 0 0; font-size: 11px; color: rgba(255,255,255,0.7); }
.online-indicator {
    display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #2ecc71;
    margin-right: 5px; box-shadow: 0 0 10px #2ecc71; animation: breathe 2s infinite;
}
@keyframes breathe { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }

.chat-logo-sm {
    width: 40px; height: 40px; border-radius: 12px; background: #ff9d2d; color: #fff;
    display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 20px;
    box-shadow: 0 5px 15px rgba(255, 157, 45, 0.3);
}
.h-content { display: flex; gap: 15px; align-items: center; }

.chat-step { flex: 1; overflow-y: auto; padding: 25px; display: flex; flex-direction: column; }
.form-intro h4 { margin: 0 0 10px; font-size: 22px; color: #1e293b; }
.form-intro p { font-size: 14px; color: #64748b; line-height: 1.6; margin-bottom: 20px; }

.chat-input-field {
    width: 100%; padding: 12px 15px; margin-bottom: 12px; border: 1.5px solid #e2e8f0;
    border-radius: 10px; font-size: 14px; outline: none; transition: 0.3s;
}
.chat-input-field:focus { border-color: #ff9d2d; box-shadow: 0 0 0 3px rgba(255, 157, 45, 0.1); }
.chat-btn-primary {
    background: #ff9d2d; color: #fff; border: none; padding: 15px; border-radius: 10px;
    font-weight: 600; font-size: 15px; cursor: pointer; transition: 0.3s; margin-top: 10px;
}
.chat-btn-primary:hover { background: #e88c1c; box-shadow: 0 5px 15px rgba(255, 157, 45, 0.3); }

.chat-messages { flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; padding: 0 5px 15px; }
.msg-bubble { 
    max-width: 85%; padding: 10px 15px; border-radius: 15px; font-size: 13.5px; line-height: 1.5;
    animation: slideUp 0.3s ease;
}
@keyframes slideUp { from { opacity: 0; transform: translateY(10px); } }

.msg-bubble.user { background: #ff9d2d; color: #fff; align-self: flex-end; border-bottom-right-radius: 4px; }
.msg-bubble.admin { background: #f1f5f9; color: #1e293b; align-self: flex-start; border-bottom-left-radius: 4px; }
.msg-bubble.system { background: #fef3c7; color: #92400e; font-style: italic; align-self: center; font-size: 12px; }

.chat-footer { padding: 15px 0; border-top: 1px solid #f1f5f9; }
#chatForm { position: relative; display: flex; align-items: center; }
.chat-input-type {
    width: 100%; padding: 12px 45px 12px 15px; border: 1.5px solid #e2e8f0; border-radius: 25px;
    font-size: 14px; outline: none; transition: 0.3s;
}
.chat-input-type:focus { border-color: #ff9d2d; }
.chat-send-btn {
    position: absolute; right: 5px; top: 5px; width: 35px; height: 35px; border: none;
    background: #ff9d2d; color: #fff; border-radius: 50%; cursor: pointer;
    display: flex; align-items: center; justify-content: center; font-size: 18px;
}
.chat-send-btn:hover { background: #e88c1c; }

/* New Premium Message Bubbles */
.order-card {
    background: #fff !important;
    border: 1px solid #e2e8f0 !important;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    padding: 10px !important;
    max-width: 90% !important;
}
.order-item-header { display: flex; gap: 12px; align-items: center; }
.order-img { width: 60px; height: 60px; border-radius: 10px; object-fit: cover; }
.order-info { display: flex; flex-direction: column; }
.order-label { font-size: 10px; text-transform: uppercase; color: #ff9d2d; font-weight: 700; }
.order-info h4 { margin: 2px 0; font-size: 14px; color: #1e293b; }
.order-price { font-size: 13px; font-weight: 700; color: #1e293b; }

.status-card {
    background: #fff !important;
    border: 1px solid #e2e8f0 !important;
    padding: 15px !important;
}
.status-badge {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 10px; border-radius: 20px; color: #fff;
    font-size: 11px; font-weight: 700; margin-bottom: 10px;
}
.status-text { font-size: 13px; color: #475569; margin: 0 0 10px; line-height: 1.4; font-style: normal !important; }
.status-meta { font-size: 11px; color: #94a3b8; border-top: 1px solid #f1f5f9; padding-top: 8px; }

.status-card .status-badge { animation: statusPulse 2s infinite; }
@keyframes statusPulse { 0% { opacity: 1; } 50% { opacity: 0.8; } 100% { opacity: 1; } }

@media (max-width: 480px) {
    .chat-window { width: calc(100vw - 40px); height: calc(100vh - 120px); bottom: 85px; right: -10px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const chatBtn = document.getElementById('chatButton');
    const chatWin = document.getElementById('chatWindow');
    const closeIcon = chatBtn.querySelector('.close-icon');
    const chatIcon = chatBtn.querySelector('.chat-icon');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInput');
    const chatMsgs = document.getElementById('chatMessages');
    const startBtn = document.getElementById('startChatBtn');
    const identityForm = document.getElementById('chatIdentityForm');
    const chatInterface = document.getElementById('chatInterface');
    const unreadBadge = chatBtn.querySelector('.unread-badge');

    let isChatOpen = false;
    let sessionId = localStorage.getItem('bloom_chat_sid') || null;
    let lastMsgId = 0;
    let unreadCount = 0;

    // Toggle Chat Window
    chatBtn.addEventListener('click', () => {
        isChatOpen = !isChatOpen;
        chatWin.style.display = isChatOpen ? 'flex' : 'none';
        setTimeout(() => chatWin.classList.toggle('active', isChatOpen), 10);
        closeIcon.style.display = isChatOpen ? 'block' : 'none';
        chatIcon.style.display = isChatOpen ? 'none' : 'block';
        
        if (isChatOpen) {
            unreadCount = 0;
            unreadBadge.style.display = 'none';
            chatMsgs.scrollTop = chatMsgs.scrollHeight;
        }
    });

    window.bloomChat = {
        open: () => { if(!isChatOpen) chatBtn.click(); },
        orderItem: (item, qty = 1) => {
            if (!isChatOpen) chatBtn.click();
            
            if (!sessionId) {
                alert("Please fill in your details first to place an order via chat.");
                document.getElementById('chatDept').value = 'Restaurant';
                return; 
            }

            // Create a modified item object with quantity
            const orderPayload = {
                ...item,
                qty: qty,
                total_price: (parseFloat(item.price) * qty).toFixed(2)
            };

            const itemMsg = `ITEM_ORDER:${JSON.stringify(orderPayload)}`;
            sendSystemMessage(itemMsg, orderPayload);
        }
    };

    function sendSystemMessage(msg, item = null) {
        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('message', msg);
        formData.append('customer_name', localStorage.getItem('bloom_chat_name'));

        appendMessage('user', msg);
        
        setTimeout(() => {
            // Ask for Payment Choice
            const choiceData = {
                title: "Choose Payment Method",
                text: "How would you like to pay for your order?",
                item_price: item ? item.price : "150.00",
                methods: [
                    { id: 'cbe', name: 'Commercial Bank (CBE)', icon: 'fa-solid fa-building-columns' },
                    { id: 'telebirr', name: 'Telebirr', icon: 'fa-solid fa-mobile-screen' }
                ]
            };

            const choiceMsg = `PAYMENT_CHOICE:${JSON.stringify(choiceData)}`;
            appendMessage('admin', choiceMsg);
            
            const autoData = new FormData();
            autoData.append('session_id', sessionId);
            autoData.append('message', "Choosing payment method...");
            autoData.append('auto_reply', choiceMsg);
            fetch('chat_handler.php', { method: 'POST', body: autoData });

        }, 800);
    }
    
    window.selectPayment = function(method, price) {
        const payData = method === 'cbe' ? {
            title: "CBE Payment Details",
            total: price,
            bank: "<?= $company_info['bank_name'] ?? 'Commercial Bank of Ethiopia (CBE)' ?>",
            account_name: "<?= $company_info['account_name'] ?? 'MEKUANINT GASHAW ASNAKE' ?>",
            account_number: "<?= $company_info['account_number'] ?? '1000580733356' ?>",
            qr: "<?= $company_info['qr_code_image'] ?? 'uploads/site/cbe_qr.jpg' ?>"
        } : {
            title: "Telebirr Payment Details",
            total: price,
            bank: "Telebirr",
            account_name: "<?= $company_info['telebirr_name'] ?? 'MEKUANINT GASHAW ASNAKE' ?>",
            account_number: "<?= $company_info['telebirr_phone'] ?? '0920123456' ?>",
            qr: "<?= $company_info['telebirr_qr'] ?? 'uploads/site/telebirr_qr.jpg' ?>"
        };

        const payMsg = `PAYMENT_REQUEST:${JSON.stringify(payData)}`;
        appendMessage('admin', payMsg);
        
        const autoData = new FormData();
        autoData.append('session_id', sessionId);
        autoData.append('message', `Paying with ${method.toUpperCase()}`);
        autoData.append('auto_reply', payMsg);
        fetch('chat_handler.php', { method: 'POST', body: autoData });
    };

    window.promptProof = function() {
        const ref = prompt("Please enter your Payment Reference Number or Transaction ID:");
        if (!ref) return;
        
        const proofMsg = `PAYMENT_PROOF:{"ref":"${ref}","text":"I have paid my order. Reference: ${ref}"}`;
        appendMessage('user', proofMsg);
        
        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('message', proofMsg);
        formData.append('customer_name', localStorage.getItem('bloom_chat_name'));
        fetch('chat_handler.php', { method: 'POST', body: formData });

        setTimeout(() => {
            const waitMsg = `STATUS_UPDATE:{"status":"Verifying","time":"2-5 min","text":"Payment reference received: ${ref}. Admin is verifying now..."}`;
            appendMessage('admin', waitMsg);
            const autoData = new FormData();
            autoData.append('session_id', sessionId);
            autoData.append('message', "Verifying payment...");
            autoData.append('auto_reply', waitMsg);
            fetch('chat_handler.php', { method: 'POST', body: autoData });
        }, 800);
    };

    // Toast notification
    function showCustomerToast(text) {
        const toast = document.createElement('div');
        toast.style = "position:fixed;bottom:110px;right:30px;background:#1e293b;color:#fff;padding:12px 20px;border-radius:15px;box-shadow:0 10px 30px rgba(0,0,0,0.2);z-index:10001;font-size:13px;display:flex;align-items:center;gap:10px;animation:slideIn 0.3s ease;cursor:pointer;";
        toast.innerHTML = `<ion-icon name="notifications" style="color:#ff9d2d;font-size:18px;"></ion-icon> <div><strong>Admin replied:</strong><br>${text.substring(0,40)}...</div>`;
        toast.onclick = () => { if(!isChatOpen) chatBtn.click(); toast.remove(); };
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 5000);
    }

    const style = document.createElement('style');
    style.innerHTML = `@keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }`;
    document.head.appendChild(style);

    const msgSound = new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3');

    if (sessionId) {
        identityForm.style.display = 'none';
        chatInterface.style.display = 'flex';
        loadHistory();
        setInterval(pollMessages, 3000);
    }

    // Load full conversation history on session restore
    function loadHistory() {
        if (!sessionId) return;
        fetch(`chat_handler.php?session_id=${sessionId}&last_id=0&_t=${Date.now()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success) return;
                // Clear the default welcome bubble
                chatMsgs.innerHTML = '<div class="msg-bubble system">Welcome! How can we help you today?</div>';
                (data.messages || []).forEach(m => {
                    appendMessage(m.sender === 'Admin' ? 'admin' : 'user', m.message, m.id);
                    if (m.id > lastMsgId) lastMsgId = m.id;
                });
                chatMsgs.scrollTop = chatMsgs.scrollHeight;
            }).catch(() => {});
    }

    function pollMessages() {
        if (!sessionId) return;
        fetch(`chat_handler.php?session_id=${sessionId}&last_id=${lastMsgId}&_t=${Date.now()}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.messages || data.messages.length === 0) return;
                let playSound = false;
                data.messages.forEach(m => {
                    if (m.id > lastMsgId) {
                        lastMsgId = m.id;
                        if (m.sender === 'Admin') {
                            appendMessage('admin', m.message, m.id);
                            playSound = true;
                            if (!isChatOpen) {
                                unreadCount++;
                                unreadBadge.style.display = 'block';
                                unreadBadge.innerText = unreadCount;
                                showCustomerToast(m.message);
                            }
                        }
                    }
                });
                if (playSound) {
                    chatMsgs.scrollTop = chatMsgs.scrollHeight;
                    msgSound.play().catch(() => {});
                }
            }).catch(() => {});
    }


    startBtn.addEventListener('click', () => {
        const name = document.getElementById('chatName').value.trim();
        const phone = document.getElementById('chatPhone').value.trim();
        const dept = document.getElementById('chatDept').value;
        if (!name || !phone) return alert('Please fill in your details.');

        const formData = new FormData();
        formData.append('customer_name', name);
        formData.append('customer_phone', phone);
        formData.append('department', dept);
        formData.append('message', "I'm starting a chat from the " + dept + " department.");

        fetch('chat_handler.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    sessionId = data.session_id;
                    localStorage.setItem('bloom_chat_sid', sessionId);
                    localStorage.setItem('bloom_chat_name', name);
                    identityForm.style.display = 'none';
                    chatInterface.style.display = 'flex';
                    chatMsgs.innerHTML = '<div class="msg-bubble system">Welcome! How can we help you today?</div>';
                    setInterval(pollMessages, 3000);
                }
            }).catch(() => {});
    });

    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const msg = chatInput.value.trim();
        if (!msg) return;

        const formData = new FormData();
        formData.append('session_id', sessionId);
        formData.append('message', msg);
        formData.append('customer_name', localStorage.getItem('bloom_chat_name'));

        appendMessage('user', msg);
        chatInput.value = '';
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
        fetch('chat_handler.php', { method: 'POST', body: formData }).catch(() => {});
    });

    function appendMessage(sender, text, msgId = null) {
        // Prevent duplicate rendering using data-msgid
        if (msgId && chatMsgs.querySelector(`[data-msgid="${msgId}"]`)) return;
        const div = document.createElement('div');
        if (msgId) div.dataset.msgid = msgId;
        if (text.startsWith('ITEM_ORDER:')) {
            try {
                const item = JSON.parse(text.replace('ITEM_ORDER:', ''));
                div.className = `msg-bubble order-card ${sender}`;
                const qtyStr = item.qty > 1 ? `<span style="font-size: 11px; color: #64748b;">Quantity: <strong>${item.qty}</strong></span>` : '';
                div.innerHTML = `
                    <div class="order-item-header">
                        <img src="${item.image_url}" class="order-img">
                        <div class="order-info">
                            <span class="order-label">Order Request ${item.qty > 1 ? '(Bulk)' : ''}</span>
                            <h4>${item.name}</h4>
                            <span class="order-price">${item.total_price || item.price} ETB</span>
                            ${qtyStr}
                        </div>
                    </div>`;
            } catch(e) { div.className = `msg-bubble ${sender}`; div.innerText = text; }
        } else if (text.startsWith('PAYMENT_CHOICE:')) {
            try {
                const choice = JSON.parse(text.replace('PAYMENT_CHOICE:', ''));
                div.className = `msg-bubble admin`;
                div.style.background = "#f1f5f9";
                div.innerHTML = `
                    <div style="padding: 15px;">
                        <span class="order-label" style="background: #334155;">PAYMENT METHOD</span>
                        <h4 style="margin: 10px 0; font-size: 14px; color: #1e293b;">${choice.text}</h4>
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            ${choice.methods.map(m => `
                                <button onclick="selectPayment('${m.id}', '${choice.item_price}')" style="background: #fff; border: 1px solid #e2e8f0; padding: 10px; border-radius: 10px; text-align: left; display: flex; align-items: center; gap: 10px; cursor: pointer;">
                                    <i class="${m.icon}" style="color: #64748b;"></i>
                                    <span style="font-weight: 700; color: #1e293b;">${m.name}</span>
                                </button>
                            `).join('')}
                        </div>
                    </div>`;
            } catch(e) { div.className = `msg-bubble ${sender}`; div.innerText = text; }
        } else if (text.startsWith('PAYMENT_REQUEST:')) {
            try {
                const pay = JSON.parse(text.replace('PAYMENT_REQUEST:', ''));
                div.className = `msg-bubble order-card admin`;
                div.style.background = "#fff8f0";
                div.innerHTML = `
                    <div class="payment-card-content" style="padding: 15px; border: 1px dashed #ff9d2d; border-radius: 12px; font-size: 13px;">
                        <span class="order-label" style="background: #ff9d2d; color: #fff; padding: 2px 8px; border-radius: 5px; font-size: 10px; font-weight: 700;">PAYMENT REQUIRED</span>
                        <h4 style="margin: 10px 0; font-size: 16px; color: #1e293b; font-weight: 800;">TOTAL: ${pay.total} ETB</h4>
                        <div class="bank-info" style="margin-bottom: 12px; color: #475569; line-height: 1.5;">
                            <div><i class="fa-solid fa-building-columns"></i> <strong>Bank/Mobile Money:</strong> ${pay.bank}</div>
                            <div><i class="fa-solid fa-id-card"></i> <strong>Account Name:</strong> ${pay.account_name}</div>
                            <div><i class="fa-solid fa-hashtag"></i> <strong>A/C or Phone:</strong> <span style="font-family: monospace; font-size: 14px; font-weight: 700; color: #ff9d2d;">${pay.account_number}</span></div>
                        </div>
                        ${pay.qr ? `<div style="text-align: center; background: #fff; padding: 10px; border-radius: 10px; border: 1px solid #efefef; margin-bottom: 12px;">
                            <img src="${pay.qr}" style="width: 150px; height: 150px; margin: 0 auto; display: block;" alt="QR Code">
                            <p style="font-size: 11px; color: #64748b; margin-top: 5px;">Scan to Pay</p>
                        </div>` : ''}
                        <button onclick="promptProof()" style="width: 100%; padding: 12px; background: var(--primary); border: none; border-radius: 8px; color: #fff; font-weight: 800; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;">
                            <i class="fa-solid fa-file-invoice"></i> Submit Payment Document
                        </button>
                    </div>`;
            } catch(e) { div.className = `msg-bubble ${sender}`; div.innerText = text; }
        } else if (text.startsWith('PAYMENT_PROOF:')) {
            try {
                const proof = JSON.parse(text.replace('PAYMENT_PROOF:', ''));
                div.className = `msg-bubble user`;
                div.style.background = "#f0fdf4";
                div.style.border = "1px solid #bbf7d0";
                div.innerHTML = `
                    <div style="padding: 10px;">
                        <div style="font-size: 10px; color: #16a34a; font-weight: 800; text-transform: uppercase;">Payment Submitted</div>
                        <div style="font-size: 14px; color: #166534; font-weight: 700; margin-top: 5px;">Reference: ${proof.ref}</div>
                    </div>`;
            } catch(e) { div.className = `msg-bubble ${sender}`; div.innerText = text; }
        } else if (text.startsWith('STATUS_UPDATE:')) {
            try {
                const status = JSON.parse(text.replace('STATUS_UPDATE:', ''));
                div.className = `msg-bubble status-card ${sender}`;
                let statusColor = '#f59e0b';
                let icon = 'time-outline';
                if (status.status === 'Confirmed') { statusColor = '#6366f1'; icon = 'wallet-outline'; }
                if (status.status === 'On Progress') { statusColor = '#3b82f6'; icon = 'fast-food-outline'; }
                if (status.status === 'Delivered') { statusColor = '#10b981'; icon = 'checkmark-circle-outline'; }
                div.innerHTML = `
                    <div class="status-badge" style="background: ${statusColor}">
                        <ion-icon name="${icon}"></ion-icon> ${status.status}
                    </div>
                    <p class="status-text">${status.text}</p>
                    <div class="status-meta"><span>Est. Time: <strong>${status.time}</strong></span></div>`;
            } catch(e) { div.className = `msg-bubble ${sender}`; div.innerText = text; }
        } else {
            div.className = `msg-bubble ${sender}`;
            div.innerHTML = text.replace(/\n/g, '<br>');
        }
        chatMsgs.appendChild(div);
        chatMsgs.scrollTop = chatMsgs.scrollHeight;
    }
});
</script>
