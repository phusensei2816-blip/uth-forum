(function () {
  const box = document.getElementById('chat-messages');
  const form = document.getElementById('chat-form');
  const textarea = document.getElementById('chat-text');
  if (!box || !form) return;

  const target = window.CHAT_TARGET || {};
  let lastId = window.LAST_ID || 0;

  function scrollToBottom() {
    box.scrollTop = box.scrollHeight;
  }
  scrollToBottom();

  function renderMessage(m) {
    const div = document.createElement('div');
    const mine = m.sender_id == window.CURRENT_USER_ID;
    div.className = 'msg ' + (mine ? 'mine' : 'theirs');
    if (target.class_id && !mine) {
      const name = document.createElement('strong');
      name.style.cssText = 'display:block;font-size:11px;opacity:.8;';
      name.textContent = m.sender_name;
      div.appendChild(name);
    }
    div.appendChild(document.createTextNode(m.content));
    box.appendChild(div);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const content = textarea.value.trim();
    if (!content) return;
    const body = new URLSearchParams({ csrf: window.CSRF, content });
    if (target.user_id) body.set('user_id', target.user_id);
    if (target.class_id) body.set('class_id', target.class_id);

    const res = await fetch('send.php', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const data = await res.json();
    if (data.ok) {
      renderMessage(data.message);
      lastId = data.message.id;
      textarea.value = '';
      scrollToBottom();
    }
  });

  async function poll() {
    const params = new URLSearchParams({ last_id: lastId });
    if (target.user_id) params.set('user_id', target.user_id);
    if (target.class_id) params.set('class_id', target.class_id);
    try {
      const res = await fetch('poll.php?' + params.toString());
      const data = await res.json();
      if (data.ok && data.messages.length) {
        data.messages.forEach(renderMessage);
        lastId = data.messages[data.messages.length - 1].id;
        scrollToBottom();
      }
    } catch (err) { /* silent retry */ }
  }

  setInterval(poll, 3000);
})();
