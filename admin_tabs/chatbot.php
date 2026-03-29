<?php
$dept_filter = $_GET['dept'] ?? 'All';
$dept_query = ($dept_filter !== 'All') ? "WHERE s.department = :dept" : "";

// Ensure tables exist before querying (prevents the 1146 Table doesn't exist error)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_sessions (
            session_id VARCHAR(50) PRIMARY KEY,
            customer_name VARCHAR(100),
            customer_email VARCHAR(100),
            customer_phone VARCHAR(50),
            department VARCHAR(50) DEFAULT 'Restaurant',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS chat_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(50),
            sender ENUM('User', 'Admin') DEFAULT 'User',
            message TEXT,
            image_path VARCHAR(255) DEFAULT NULL,
            location_lat VARCHAR(50) DEFAULT NULL,
            location_lng VARCHAR(50) DEFAULT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (session_id) REFERENCES chat_sessions(session_id) ON DELETE CASCADE
        );
    ");
    // Add missing columns to existing tables safely
    $new_chat_cols = ['image_path' => 'VARCHAR(255)', 'location_lat' => 'VARCHAR(50)', 'location_lng' => 'VARCHAR(50)'];
    foreach ($new_chat_cols as $c => $type) {
        try { $pdo->exec("ALTER TABLE chat_messages ADD COLUMN `$c` $type DEFAULT NULL"); } catch (PDOException $e) { }
    }
} catch (PDOException $e) {
    // Ignore error if permissions restrict creation
}

$q = "SELECT m.session_id, MAX(m.id) as max_id, MAX(m.created_at) as last_msg, s.customer_name, s.customer_phone, s.department,
      SUM(CASE WHEN m.sender = 'User' AND m.is_read = 0 THEN 1 ELSE 0 END) as unread_count
      FROM chat_messages m
      LEFT JOIN chat_sessions s ON m.session_id = s.session_id
      $dept_query
      GROUP BY m.session_id ORDER BY last_msg DESC";
$stmt = $pdo->prepare($q);
if ($dept_filter !== 'All') { $stmt->execute(['dept' => $dept_filter]); } else { $stmt->execute(); }
$sessions = $stmt->fetchAll();
$active_sid = $_GET['sid'] ?? ($sessions[0]['session_id'] ?? '');
$messages = []; $active_customer = null; $last_msg_id = 0;
if ($active_sid) {
    $stmt = $pdo->prepare("SELECT * FROM chat_messages WHERE session_id = ? ORDER BY created_at ASC");
    $stmt->execute([$active_sid]); $messages = $stmt->fetchAll();
    $stmt = $pdo->prepare("SELECT * FROM chat_sessions WHERE session_id = ?");
    $stmt->execute([$active_sid]); $active_customer = $stmt->fetch();
    foreach ($messages as $m) { if ($m['id'] > $last_msg_id) $last_msg_id = $m['id']; }
    $pdo->prepare("UPDATE chat_messages SET is_read = 1 WHERE session_id = ? AND sender = 'User'")->execute([$active_sid]);
}
?>
<div style="display:flex;gap:30px;height:75vh;">
  <div class="card" style="width:320px;display:flex;flex-direction:column;overflow:hidden;padding:0;">
    <div class="card-header" style="padding:20px;border-bottom:none;"><span class="card-title">Live Conversations</span></div>
    <div style="padding:0 20px 15px;border-bottom:1px solid #f0f0f0;">
      <select onchange="window.location.href='?tab=chatbot&dept='+this.value" style="width:100%;padding:8px;border-radius:6px;border:1px solid #ccc;font-size:13px;">
        <option value="All" <?= $dept_filter=='All'?'selected':'' ?>>All Departments</option>
        <option value="Restaurant" <?= $dept_filter=='Restaurant'?'selected':'' ?>>Restaurant</option>
        <option value="Construction" <?= $dept_filter=='Construction'?'selected':'' ?>>Construction</option>
      </select>
    </div>
    <div style="flex:1;overflow-y:auto;">
      <?php foreach ($sessions as $s): ?>
      <a href="?tab=chatbot&dept=<?=urlencode($dept_filter)?>&sid=<?=$s['session_id']?>"
         id="side-<?=$s['session_id']?>" data-sid="<?=$s['session_id']?>"
         style="display:block;padding:15px 20px;text-decoration:none;border-bottom:1px solid #f0f0f0;background:<?=($active_sid==$s['session_id'])?'#f8fafc':'transparent'?>;border-left:4px solid <?=($active_sid==$s['session_id'])?'var(--blue)':'transparent'?>;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:5px;">
          <span class="cust-name" style="font-weight:600;color:#333;font-size:13px;">
            <?=$s['customer_name']?htmlspecialchars($s['customer_name']):'Guest #'.substr($s['session_id'],0,4)?>
            <span style="font-size:9px;background:#e2e8f0;padding:2px 4px;border-radius:3px;font-weight:normal;margin-left:5px;"><?=htmlspecialchars($s['department']??'Restaurant')?></span>
          </span>
          <div style="display:flex;flex-direction:column;align-items:flex-end;gap:5px;">
            <small style="color:#94a3b8;font-size:10px;"><?=date('H:i',strtotime($s['last_msg']))?></small>
            <?php if ($s['unread_count']>0): ?>
            <span class="nav-badge" style="margin:0;padding:2px 6px;font-size:9px;"><?=$s['unread_count']?></span>
            <?php endif; ?>
          </div>
        </div>
        <?php if ($s['customer_phone']): ?><div style="font-size:11px;color:#64748b;"><i class="fa-solid fa-phone" style="font-size:9px;"></i> <?=htmlspecialchars($s['customer_phone'])?></div><?php endif; ?>
      </a>
      <?php endforeach; ?>
      <?php if (empty($sessions)): ?><p style="padding:20px;color:#888;text-align:center;">No active chats.</p><?php endif; ?>
    </div>
  </div>

  <div class="card" style="flex:1;display:flex;flex-direction:column;padding:0;overflow:hidden;">
    <?php if ($active_sid): ?>
    <div class="card-header" style="background:#fdfdfd;padding:15px 20px;border-bottom:1px solid #f0f0f0;display:flex;justify-content:space-between;align-items:center;">
      <div style="display:flex;align-items:center;gap:12px;">
        <div style="width:40px;height:40px;border-radius:50%;background:#e2e8f0;display:flex;align-items:center;justify-content:center;color:#64748b;font-weight:700;"><?=strtoupper(substr($active_customer['customer_name']??'G',0,1))?></div>
        <div>
          <div style="font-weight:700;color:#1e293b;font-size:14px;"><?=$active_customer['customer_name']?htmlspecialchars($active_customer['customer_name']):'Guest User'?></div>
          <div style="font-size:11px;color:#64748b;"><?=htmlspecialchars($active_customer['customer_email']??'')?> &bull; <?=htmlspecialchars($active_customer['customer_phone']??'')?></div>
        </div>
      </div>
      <div>
        <span class="badge" style="background:#e0fdf4;color:#059669;margin-right:8px;"><i class="fa-solid fa-circle" style="font-size:7px;margin-right:4px;"></i>Active</span>
        <span class="badge" style="background:#f1f5f9;color:#64748b;"><?=htmlspecialchars($active_customer['department']??'Restaurant')?></span>
      </div>
    </div>
    <div id="chatBox" style="flex:1;padding:30px;overflow-y:auto;background:#fff;display:flex;flex-direction:column;gap:15px;">
      <?php foreach ($messages as $m): 
        $text = $m['message'];
        $bubble_html = nl2br(htmlspecialchars($text));
        $is_order = strpos($text, 'ITEM_ORDER:') === 0;
        $is_status = strpos($text, 'STATUS_UPDATE:') === 0;
        
        if ($is_order) {
            $item = json_decode(substr($text, 11), true);
            if ($item) {
                $qty = $item['qty'] ?? 1;
                $display_price = $item['total_price'] ?? ($item['price'] * $qty);
                $bubble_html = '<div style="display:flex;gap:12px;align-items:center;padding:5px;">
                    <img src="'.htmlspecialchars($item['image_url']).'" style="width:50px;height:50px;border-radius:8px;object-fit:cover;">
                    <div>
                        <div style="font-size:10px;text-transform:uppercase;color:var(--deep-saffron);font-weight:700;">Order Request '.($qty > 1 ? '(Bulk)' : '').'</div>
                        <div style="font-weight:700;font-size:13px;color:#1e293b;">'.htmlspecialchars($item['name']).'</div>
                        <div style="font-weight:700;font-size:12px;">'.number_format($display_price, 2).' ETB</div>
                        '.($qty > 1 ? '<div style="font-size:10px;color:#64748b;">Quantity: <strong>'.$qty.'</strong></div>' : '').'
                    </div>
                </div>';
            }
        } elseif (strpos($text, 'PAYMENT_CHOICE:') === 0) {
            $choice = json_decode(substr($text, 15), true);
            if ($choice) {
                $bubble_html = '<div style="padding:5px; opacity: 0.7;">
                    <div style="font-size:10px;text-transform:uppercase;color:#64748b;font-weight:700;">Asking for payment method...</div>
                </div>';
            }
        } elseif (strpos($text, 'PAYMENT_PROOF:') === 0) {
            $proof = json_decode(substr($text, 14), true);
            if ($proof) {
                $bubble_html = '<div style="padding:10px; border: 2px solid #10b981; border-radius:12px; background:#f0fdf4;">
                    <div style="font-size:10px;text-transform:uppercase;color:#10b981;font-weight:800;margin-bottom:5px;">📥 Payment Proof Received</div>
                    <div style="font-weight:800;font-size:16px;color:#166534;margin-bottom:10px;">Ref: '.htmlspecialchars($proof['ref']).'</div>
                    <div style="display:flex;gap:8px;">
                        <button onclick="confirmPayment(\'Ref: '.htmlspecialchars($proof['ref']).'\')" style="flex:1;background:#10b981;color:#fff;border:none;padding:8px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">Accept</button>
                        <button onclick="rejectPayment(\''.htmlspecialchars($proof['ref']).'\')" style="flex:1;background:#ef4444;color:#fff;border:none;padding:8px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">Reject</button>
                    </div>
                </div>';
            }
        } elseif (strpos($text, 'PAYMENT_REQUEST:') === 0) {
            $pay = json_decode(substr($text, 16), true);
            if ($pay) {
                $bubble_html = '<div style="padding:5px; border: 1.5px dashed #f6993f44; border-radius:10px; background:#fffcf9;">
                    <div style="font-size:10px;text-transform:uppercase;color:#f6993f;font-weight:800;margin-bottom:5px;">💸 Payment Request Sent</div>
                    <div style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:5px;">Total: '.htmlspecialchars($pay['total']).' ETB</div>
                    <div style="font-size:11px;color:#64748b;margin-bottom:10px;">Bank: '.htmlspecialchars($pay['bank']).'</div>
                    <button onclick="confirmPayment(\''.htmlspecialchars($pay['total']).'\')" style="width:100%;background:#10b981;color:#fff;border:none;padding:6px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;">
                        <i class="fa-solid fa-circle-check"></i> Confirm Payment
                    </button>
                </div>';
            }
        } elseif ($is_status) {
            $status = json_decode(substr($text, 14), true);
            if ($status) {
                $color = $status['status'] === 'Delivered' ? '#10b981' : ($status['status'] === 'On Progress' ? '#3b82f6' : ($status['status'] === 'Confirmed' ? '#6366f1' : '#f59e0b'));
                $bubble_html = '<div style="padding:5px;">
                    <div style="display:inline-block;padding:3px 10px;border-radius:15px;background:'.$color.';color:#fff;font-size:10px;font-weight:700;margin-bottom:8px;">'.htmlspecialchars($status['status']).'</div>
                    <div style="font-size:13px;margin-bottom:5px;">'.htmlspecialchars($status['text']).'</div>
                    <div style="font-size:11px;opacity:0.8;">Est. Time: <strong>'.htmlspecialchars($status['time']).'</strong></div>
                </div>';
            }
        }
      ?>
      <div class="msg-bubble" data-msgid="<?=$m['id']?>" style="max-width:85%;padding:12px 18px;border-radius:12px;font-size:14px;line-height:1.5;margin-bottom:10px;<?=($m['sender']=='User')?'background:#f1f5f9;color:#1e293b;align-self:flex-start;':'background:var(--blue);color:#fff;align-self:flex-end;'?>">
        <strong style="font-size:11px;"><?=($m['sender']=='User')?htmlspecialchars($active_customer['customer_name']??'User'):'Admin'?>:</strong><br>
        <?=$bubble_html?>
        <div style="font-size:10px;opacity:0.7;margin-top:5px;text-align:right;"><?=date('H:i',strtotime($m['created_at']))?></div>
      </div>
      <?php endforeach; ?>
    </div>
    <div style="padding:20px;background:#f8fafc;border-top:1px solid #f0f0f0;">
      <form id="adminReplyForm" style="display:flex;gap:15px;">
        <input type="hidden" name="send_chat_reply" value="1">
        <input type="hidden" name="session_id" value="<?=$active_sid?>">
        <input type="text" name="reply" id="adminReplyInput" placeholder="Reply to <?=htmlspecialchars($active_customer['customer_name']??'customer')?>..." required style="flex:1;padding:12px 20px;border:1px solid #e2e8f0;border-radius:10px;outline:none;">
        <button type="submit" class="btn btn-primary" style="margin:0;padding:0 30px;"><i class="fa-solid fa-paper-plane"></i> Send</button>
      </form>
    </div>
    <?php else: ?>
    <div style="flex:1;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#94a3b8;">
      <i class="fa-solid fa-comment-dots" style="font-size:50px;margin-bottom:20px;opacity:0.3;"></i>
      <p>Select a customer to start chatting.</p>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Toast notification container -->
<div id="adminToastBox" style="position:fixed;top:80px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:10px;pointer-events:none;"></div>
<audio id="msgSound" src="https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3" preload="auto"></audio>

<style>
.a-toast{background:#1e293b;color:#fff;padding:14px 18px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,.4);display:flex;align-items:flex-start;gap:12px;min-width:290px;max-width:370px;pointer-events:auto;cursor:pointer;border-left:4px solid #f39c12;animation:tsIn .4s cubic-bezier(.175,.885,.32,1.275);transition:opacity .4s,transform .4s;}
@keyframes tsIn{from{opacity:0;transform:translateX(50px)}to{opacity:1;transform:translateX(0)}}
.a-toast .ti{width:38px;height:38px;background:rgba(243,156,18,.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:16px;color:#f39c12;flex-shrink:0;}
.a-toast .tb{flex:1;min-width:0;}
.a-toast .tb strong{display:block;font-size:13px;color:#f39c12;margin-bottom:3px;}
.a-toast .tb span{font-size:12px;color:rgba(255,255,255,.75);line-height:1.4;}
.a-toast .tc{margin-left:auto;font-size:15px;color:#64748b;cursor:pointer;flex-shrink:0;padding:0 4px;}
.a-toast .tdept{font-size:9px;background:rgba(243,156,18,.2);color:#f39c12;padding:1px 6px;border-radius:10px;margin-left:5px;font-weight:700;text-transform:uppercase;}
</style>

<script>
const cb         = document.getElementById('chatBox');
let lastId       = <?= (int)$last_msg_id ?>;
const activeSid  = '<?= $active_sid ?>';
const deptFilter = '<?= $dept_filter ?>';
const sound      = document.getElementById('msgSound');
const origTitle  = document.title;
let flashTimer   = null;
if (cb) cb.scrollTop = cb.scrollHeight;

// Request OS notification permission
if ('Notification' in window && Notification.permission === 'default') Notification.requestPermission();

function showToast(name, dept, text, sid) {
    const box = document.getElementById('adminToastBox');
    const t = document.createElement('div');
    t.className = 'a-toast';
    t.innerHTML = `<div class="ti"><i class="fa-solid fa-comment-dots"></i></div>
        <div class="tb"><strong>💬 ${name}<span class="tdept">${dept||'Chat'}</span></strong>
        <span>${(text||'New message').substring(0,80)}</span></div>
        <span class="tc">✕</span>`;
    t.querySelector('.tc').onclick = e => { e.stopPropagation(); fadeT(t); };
    t.onclick = () => window.location.href = `?tab=chatbot&dept=${deptFilter}&sid=${sid}`;
    box.appendChild(t);
    setTimeout(() => fadeT(t), 8000);
}
function fadeT(el){ if(!el||!el.parentNode)return; el.style.opacity='0'; el.style.transform='translateX(50px)'; setTimeout(()=>{if(el.parentNode)el.remove();},400); }

function flashTitle(name){
    if(flashTimer)return;
    let on=true;
    flashTimer=setInterval(()=>{document.title=on?`🔔 ${name} sent a message!`:origTitle;on=!on;},1000);
    setTimeout(()=>{clearInterval(flashTimer);flashTimer=null;document.title=origTitle;},15000);
}

function osPush(name, dept, text){
    if('Notification' in window && Notification.permission==='granted'){
        try{
            const n=new Notification(`💬 ${name} - Bloom Chat`,{body:`[${dept}] ${(text||'').substring(0,100)}`,icon:'/favicon.ico',tag:'bloom-chat'});
            n.onclick=()=>window.focus();
        }catch(e){}
    }
}

function confirmPayment(amount) {
    if(!confirm(`Confirm that you have received ${amount} ETB?`)) return;
    const statusMsg = `STATUS_UPDATE:{"status":"Confirmed","time":"10-15 min","text":"Payment of ${amount} ETB confirmed! We are now finalizing your food and it will be delivered soon."}`;
    
    const formData = new FormData();
    formData.append('session_id', activeSid);
    formData.append('message', "Payment confirmed.");
    formData.append('auto_reply', statusMsg);
    
    fetch('chat_handler.php', { method: 'POST', body: formData })
        .then(() => {
            alert("Payment confirmed and customer notified!");
            // Refresh messages immediately
            pollChat();
        });
}

function rejectPayment(ref) {
    const reason = prompt(`Reject payment reference ${ref}? Why?`, "Transaction not found");
    if(!reason) return;
    
    const statusMsg = `STATUS_UPDATE:{"status":"Rejected","time":"N/A","text":"Payment rejected: ${reason}. Please submit a valid transaction ID."}`;
    
    const formData = new FormData();
    formData.append('session_id', activeSid);
    formData.append('message', `Proof rejected: ${reason}`);
    formData.append('auto_reply', statusMsg);
    
    fetch('chat_handler.php', { method: 'POST', body: formData }).then(() => pollChat());
}

function renderMessageHTML(sender, text) {
    if (text.startsWith('ITEM_ORDER:')) {
        try {
            const item = JSON.parse(text.replace('ITEM_ORDER:', ''));
            const qty = item.qty || 1;
            const price = item.total_price || (parseFloat(item.price) * qty).toFixed(2);
            return `<div style="display:flex;gap:12px;align-items:center;padding:5px;">
                <img src="${item.image_url}" style="width:50px;height:50px;border-radius:8px;object-fit:cover;">
                <div>
                    <div style="font-size:10px;text-transform:uppercase;color:#f6993f;font-weight:700;">Order Request ${qty > 1 ? '(Bulk)' : ''}</div>
                    <div style="font-weight:700;font-size:13px;color:#1e293b;">${item.name}</div>
                    <div style="font-weight:700;font-size:12px;">${price} ETB</div>
                    ${qty > 1 ? `<div style="font-size:10px;color:#64748b;">Quantity: <strong>${qty}</strong></div>` : ''}
                </div>
            </div>`;
        } catch(e) { return text; }
    } else if (text.startsWith('PAYMENT_CHOICE:')) {
        return `<div style="padding:5px; opacity: 0.6; font-size:11px;">Asking Customer for payment method...</div>`;
    } else if (text.startsWith('PAYMENT_PROOF:')) {
        try {
            const proof = JSON.parse(text.replace('PAYMENT_PROOF:', ''));
            return `<div style="padding:10px; border: 2px solid #10b981; border-radius:12px; background:#f0fdf4;">
                <div style="font-size:10px;text-transform:uppercase;color:#10b981;font-weight:800;margin-bottom:5px;">📥 Payment Proof Received</div>
                <div style="font-weight:800;font-size:16px;color:#166534;margin-bottom:10px;">Ref: ${proof.ref}</div>
                <div style="display:flex;gap:8px;">
                    <button onclick="confirmPayment('Ref: ${proof.ref}')" style="flex:1;background:#10b981;color:#fff;border:none;padding:8px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">Accept</button>
                    <button onclick="rejectPayment('${proof.ref}')" style="flex:1;background:#ef4444;color:#fff;border:none;padding:8px;border-radius:8px;font-size:11px;font-weight:700;cursor:pointer;">Reject</button>
                </div>
            </div>`;
        } catch(e) { return text; }
    } else if (text.startsWith('PAYMENT_REQUEST:')) {
        try {
            const pay = JSON.parse(text.replace('PAYMENT_REQUEST:', ''));
            return `<div style="padding:5px; border: 1.5px dashed #f6993f44; border-radius:10px; background:#fffcf9;">
                <div style="font-size:10px;text-transform:uppercase;color:#f6993f;font-weight:800;margin-bottom:5px;">💸 Payment Details Shared</div>
                <div style="font-weight:800;font-size:15px;color:#1e293b;margin-bottom:5px;">Total: ${pay.total} ETB</div>
                <div style="font-size:11px;color:#64748b;margin-bottom:5px;">Bank: ${pay.bank}<br>Acc: ${pay.account_number}</div>
            </div>`;
        } catch(e) { return text; }
    } else if (text.startsWith('STATUS_UPDATE:')) {
        try {
            const status = JSON.parse(text.replace('STATUS_UPDATE:', ''));
            const colorMap = {
                'Delivered': '#10b981',
                'Confirmed': '#6366f1',
                'On Progress': '#3b82f6',
                'Verifying': '#8b5cf6',
                'Rejected': '#ef4444',
                'Pending': '#f59e0b'
            };
            const color = colorMap[status.status] || '#f59e0b';
            return `<div style="padding:5px;">
                <div style="display:inline-block;padding:3px 10px;border-radius:15px;background:${color};color:#fff;font-size:10px;font-weight:700;margin-bottom:8px;">${status.status}</div>
                <div style="font-size:13px;margin-bottom:5px;">${status.text}</div>
                <div style="font-size:11px;opacity:0.8;">Est. Time: <strong>${status.time}</strong></div>
            </div>`;
        } catch(e) { return text; }
    }
    return text.replace(/\n/g,'<br>');
}

function pollChat() {
    fetch(`admin_chat_fetch.php?sid=${activeSid}&last_id=${lastId}&dept=${deptFilter}&_t=${Date.now()}`)
        .then(r=>r.json()).then(data=>{
        if(!data.success) return;

        (data.sessions||[]).forEach(s=>{
            const link=document.getElementById(`side-${s.session_id}`);
            if(!link) return;
            let badge=link.querySelector('.nav-badge');
            if(s.unread_count>0){
                const nm=(link.querySelector('.cust-name')?.innerText||'Customer').split('\n')[0].trim();
                const isNew = !badge || badge.innerText != s.unread_count;
                if(badge){ badge.innerText=s.unread_count; } else {
                    badge=document.createElement('span'); badge.className='nav-badge';
                    badge.style='margin:0;padding:2px 6px;font-size:9px;'; badge.innerText=s.unread_count;
                    const hd=link.querySelector('div'); if(hd&&hd.children[1]) hd.children[1].appendChild(badge);
                }
                if(isNew && s.session_id!==activeSid){
                    sound.play().catch(()=>{});
                    showToast(nm,s.department,'New message',s.session_id);
                    osPush(nm,s.department,'New message');
                    flashTitle(nm);
                }
            } else if(badge){ badge.remove(); }
        });

        if(activeSid && cb && (data.messages||[]).length>0){
            let play=false;
            data.messages.forEach(m=>{
                if(m.id<=lastId) return;
                lastId=m.id;
                
                if(m.sender==='User'){
                    const div=document.createElement('div');
                    div.className='msg-bubble'; div.dataset.msgid=m.id;
                    div.style=`max-width:85%;padding:12px 18px;border-radius:12px;font-size:14px;line-height:1.5;margin-bottom:10px;background:#f1f5f9;color:#1e293b;align-self:flex-start;`;
                    const ts=new Date(m.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',hour12:false});
                    div.innerHTML=`<strong style="font-size:11px;">${'<?= addslashes($active_customer['customer_name']??'User') ?>'}:</strong><br>${renderMessageHTML('User', m.message)}<div style="font-size:10px;opacity:.7;margin-top:5px;text-align:right;">${ts}</div>`;
                    cb.appendChild(div);
                    play=true;
                    const n='<?= addslashes($active_customer['customer_name']??'Customer') ?>';
                    const d='<?= addslashes($active_customer['department']??'') ?>';
                    showToast(n,d,m.message.substring(0,80),activeSid);
                    osPush(n,d,m.message);
                    flashTitle(n);
                }
            });
            if(play) { cb.scrollTop=cb.scrollHeight; sound.play().catch(()=>{}); }
        }
    }).catch(()=>{});
}

const replyForm=document.getElementById('adminReplyForm');
if(replyForm){
    replyForm.onsubmit=function(e){
        e.preventDefault();
        const inp=document.getElementById('adminReplyInput');
        const msgText = inp.value.trim();
        if(!msgText) return;
        
        const div=document.createElement('div');
        div.className='msg-bubble';
        div.style='max-width:85%;padding:12px 18px;border-radius:12px;font-size:14px;line-height:1.5;margin-bottom:10px;background:var(--blue);color:#fff;align-self:flex-end;';
        const ts=new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',hour12:false});
        div.innerHTML=`<strong style="font-size:11px;">Admin:</strong><br>${renderMessageHTML('Admin', msgText)}<div style="font-size:10px;opacity:.7;margin-top:5px;text-align:right;">${ts}</div>`;
        cb.appendChild(div);
        cb.scrollTop=cb.scrollHeight;
        
        const fd = new FormData(replyForm);
        fd.append('ajax', '1');
        inp.value='';
        
        fetch('admin.php?tab=chatbot&dept='+deptFilter,{
            method:'POST',
            body:fd,
            headers: {'X-Requested-With': 'XMLHttpRequest'}
        }).then(r=>r.json()).then(data=>{ if(data.success) pollChat(); });
    };
}

setInterval(pollChat,3000);
</script>