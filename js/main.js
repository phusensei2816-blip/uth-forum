// ---------- Rich text editor (lightweight, execCommand based) ----------
document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.editor-toolbar').forEach(toolbar => {
    const targetId = toolbar.dataset.target;
    const editor = document.getElementById(targetId);
    const hidden = document.getElementById(targetId + '_hidden');

    toolbar.querySelectorAll('button[data-cmd]').forEach(btn => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        const cmd = btn.dataset.cmd;
        if (cmd === 'createLink') {
          const url = prompt('Nhập đường dẫn liên kết:', 'https://');
          if (url) document.execCommand(cmd, false, url);
        } else {
          document.execCommand(cmd, false, null);
        }
        editor.focus();
      });
    });

    // Sync contenteditable HTML into a hidden field before submit
    const form = editor.closest('form');
    if (form) {
      form.addEventListener('submit', () => {
        hidden.value = editor.innerHTML.trim();
      });
    }
  });

  // ---------- Like toggle (AJAX) ----------
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const postId = btn.dataset.postId;
      const res = await fetch('/uth-forum-main/like_toggle.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'post_id=' + encodeURIComponent(postId) + '&csrf=' + encodeURIComponent(window.CSRF || '')
      });
      const data = await res.json();
      if (data.ok) {
        btn.classList.toggle('liked', data.liked);
        btn.querySelector('.count').textContent = data.count;
      }
    });
  });
});
