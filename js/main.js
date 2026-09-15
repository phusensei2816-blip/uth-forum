// ---------- UTH Forum main JS ----------
document.addEventListener('DOMContentLoaded', () => {

  // ---------- Rich text editor ----------
  document.querySelectorAll('.editor-toolbar').forEach(toolbar => {
    const targetId = toolbar.dataset.target;
    const editor = document.getElementById(targetId);
    const hidden = document.getElementById(targetId + '_hidden');

    if (!editor || !hidden) return;

    toolbar.querySelectorAll('button[data-cmd]').forEach(btn => {
      btn.addEventListener('click', e => {
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

    const form = editor.closest('form');
    if (form) {
      form.addEventListener('submit', () => {
        hidden.value = editor.innerHTML.trim();
      });
    }
  });

  // ---------- Helpers ----------
  function updateLikeUI(liked, count) {
    document.querySelectorAll('.like-count').forEach(el => {
      el.textContent = count;
    });

    const likeBtn = document.querySelector('.like-btn');
    if (likeBtn) {
      likeBtn.classList.toggle('liked', !!liked);

      const text = likeBtn.querySelector('.like-text');
      if (text) text.textContent = liked ? 'Đã thích' : 'Thích';
    }
  }

  function updateCommentCount(count) {
    document.querySelectorAll('.fb-comment-total').forEach(el => {
      el.textContent = count;
    });

    document.querySelectorAll('.comment-count').forEach(el => {
      el.textContent = count + ' bình luận';
    });
  }

  function escapeHtml(value) {
    return String(value ?? '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function getLatestCommentId() {
    let maxId = 0;

    document.querySelectorAll('.fb-comment[data-comment-id]').forEach(el => {
      const id = Number(el.dataset.commentId || 0);
      if (id > maxId) maxId = id;
    });

    return maxId;
  }

  function appendRealtimeComment(c) {
    const list = document.querySelector('.fb-comments-list');
    if (!list || !c) return;

    const commentId = Number(c.id);

    // Chống comment bị thêm 2 lần
    const alreadyExists = Array.from(
      list.querySelectorAll('.fb-comment[data-comment-id]')
    ).some(el => Number(el.dataset.commentId) === commentId);

    if (alreadyExists) return;

    const empty = list.querySelector('.no-comments');
    if (empty) empty.remove();

    const name = c.full_name || c.username || 'Người dùng';
    const avatarLetter = name.trim().charAt(0).toUpperCase() || '?';
    const avatarHtml = c.avatar
  ? `<img src="${escapeHtml(String(c.avatar).replace(/^\//, ''))}" alt="Ảnh đại diện">`
  : escapeHtml(avatarLetter);

    const comment = document.createElement('div');
    comment.className = 'fb-comment';
    comment.dataset.commentId = commentId;

    comment.innerHTML = `
      <div class="avatar fb-comment-avatar">
        ${avatarHtml}
      </div>

      <div class="fb-comment-content">
        <div class="fb-comment-bubble">
          <div class="fb-comment-name">
            ${escapeHtml(name)}
          </div>
          <div class="fb-comment-text">
            ${escapeHtml(c.content)}
          </div>
        </div>

        <div class="fb-comment-meta">
          vừa xong
        </div>
      </div>
    `;

    list.appendChild(comment);
    return comment;
  }

  // ---------- Like AJAX ----------
  document.querySelectorAll('.like-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      if (btn.disabled) return;

      const postId = btn.dataset.postId;

      try {
        const res = await fetch('like_toggle.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body:
            'post_id=' + encodeURIComponent(postId) +
            '&csrf=' + encodeURIComponent(window.CSRF || '')
        });

        const data = await res.json();

        if (!data.ok) {
          if (data.error === 'not_logged_in') {
            location.href = 'login.php';
          }
          return;
        }

        updateLikeUI(data.liked, data.count);
      } catch (error) {
        console.error('Like error:', error);
      }
    });
  });

  // ---------- Scroll tới bình luận ----------
  document.querySelectorAll('.comment-scroll-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      const comments = document.getElementById('comments');
      if (!comments) return;

      comments.scrollIntoView({
        behavior: 'smooth',
        block: 'start'
      });

      const input = document.getElementById('commentInput');
      if (input) {
        setTimeout(() => input.focus(), 500);
      }
    });
  });

  // ---------- Comment AJAX ----------
  const commentForm = document.getElementById('commentForm');

  if (commentForm) {
    commentForm.addEventListener('submit', async e => {
      e.preventDefault();

      const input = document.getElementById('commentInput');
      const submitBtn = commentForm.querySelector('.fb-send-comment');

      if (!input || !submitBtn) return;

      const content = input.value.trim();
      if (!content) return;

      submitBtn.disabled = true;

      try {
        const res = await fetch(commentForm.action, {
          method: 'POST',
          body: new FormData(commentForm),
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });

        const data = await res.json();

        if (!data.ok) {
          alert('Không thể gửi bình luận.');
          return;
        }

        const comment = appendRealtimeComment(data.comment);

        input.value = '';
        updateCommentCount(data.count);

        // Cập nhật mốc comment để polling không thêm lại comment vừa gửi
        window.latestCommentId = Math.max(
          Number(window.latestCommentId || 0),
          Number(data.comment?.id || 0)
        );

        if (comment) {
          comment.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest'
          });
        }
      } catch (error) {
        console.error('Comment error:', error);
        alert('Có lỗi xảy ra khi gửi bình luận.');
      } finally {
        submitBtn.disabled = false;
        input.focus();
      }
    });

    // Tự mở rộng ô comment
    const input = document.getElementById('commentInput');
    if (input) {
      input.addEventListener('input', () => {
        input.style.height = 'auto';
        input.style.height = Math.min(input.scrollHeight, 120) + 'px';
      });
    }
  }

  // ---------- REAL-TIME Like + Comment ----------
  // Polling ổn định cho PHP: đồng bộ mỗi 1 giây, không cần F5.
  const postId = Number(
    window.POST_ID ||
    document.querySelector('.fb-comments-card')?.dataset.postId ||
    document.querySelector('.like-btn')?.dataset.postId ||
    0
  );

  if (postId > 0) {
    let realtimeBusy = false;
    let lastRealtimeVersion = '';

    function getCommentMap() {
      const map = new Map();
      document.querySelectorAll('.fb-comment[data-comment-id]').forEach(el => {
        map.set(Number(el.dataset.commentId), el);
      });
      return map;
    }

    function formatTimeAgo(dateString) {
      if (!dateString) return 'vừa xong';

      const raw = String(dateString).replace(' ', 'T');
      const date = new Date(raw);
      if (Number.isNaN(date.getTime())) return 'vừa xong';

      const seconds = Math.max(0, Math.floor((Date.now() - date.getTime()) / 1000));
      if (seconds < 10) return 'vừa xong';
      if (seconds < 60) return seconds + ' giây trước';

      const minutes = Math.floor(seconds / 60);
      if (minutes < 60) return minutes + ' phút trước';

      const hours = Math.floor(minutes / 60);
      if (hours < 24) return hours + ' giờ trước';

      const days = Math.floor(hours / 24);
      if (days < 7) return days + ' ngày trước';

      return date.toLocaleDateString('vi-VN');
    }

    function buildComment(c) {
      const name = c.full_name || c.username || 'Người dùng';
      const avatarLetter = name.trim().charAt(0).toUpperCase() || '?';
      const avatarHtml = c.avatar
      ? `<img src="${escapeHtml(String(c.avatar).replace(/^\//, ''))}" alt="Ảnh đại diện">`
      : escapeHtml(avatarLetter);

      const comment = document.createElement('div');
      comment.className = 'fb-comment';
      comment.dataset.commentId = Number(c.id);
      comment.dataset.createdAt = c.created_at || '';

      comment.innerHTML = `
        <div class="avatar fb-comment-avatar">
          ${avatarHtml}
        </div>

        <div class="fb-comment-content">
          <div class="fb-comment-bubble">
            <div class="fb-comment-name">
              ${escapeHtml(name)}
            </div>
            <div class="fb-comment-text">
              ${escapeHtml(c.content)}
            </div>
          </div>

          <div class="fb-comment-meta">
            <span class="fb-comment-time">${escapeHtml(formatTimeAgo(c.created_at))}</span>
            ${c.can_delete ? `
              <a href="comment_delete.php?id=${encodeURIComponent(c.id)}&post_id=${encodeURIComponent(postId)}"
                 class="fb-realtime-delete"
                 onclick="return confirm('Xóa bình luận này?')">Xóa</a>
            ` : ''}
          </div>
        </div>
      `;

      return comment;
    }

    function syncComments(comments) {
      const list = document.querySelector('.fb-comments-list');
      if (!list || !Array.isArray(comments)) return;

      const incoming = new Map();
      comments.forEach(c => incoming.set(Number(c.id), c));

      // Xóa comment đã bị xóa ở máy khác.
      list.querySelectorAll('.fb-comment[data-comment-id]').forEach(el => {
        const id = Number(el.dataset.commentId);
        if (!incoming.has(id)) el.remove();
      });

      let addedNew = false;
      const current = getCommentMap();

      comments.forEach(c => {
        const id = Number(c.id);
        if (current.has(id)) {
          // Cập nhật nội dung/thời gian nếu comment thay đổi.
          const el = current.get(id);
          const text = el.querySelector('.fb-comment-text');
          const time = el.querySelector('.fb-comment-time');
          if (text) text.textContent = c.content || '';
          if (time) time.textContent = formatTimeAgo(c.created_at);
          el.dataset.createdAt = c.created_at || '';
          return;
        }

        list.appendChild(buildComment(c));
        addedNew = true;
      });

      const empty = list.querySelector('.no-comments');
      if (comments.length > 0 && empty) empty.remove();

      if (comments.length === 0 && !list.querySelector('.no-comments')) {
        const emptyBox = document.createElement('div');
        emptyBox.className = 'no-comments';
        emptyBox.textContent = 'Chưa có bình luận nào. Hãy là người đầu tiên bình luận!';
        list.appendChild(emptyBox);
      }

      // Chỉ tự cuộn khi người dùng đang ở gần cuối danh sách.
      if (addedNew) {
        const nearBottom = window.innerHeight + window.scrollY >= document.body.offsetHeight - 250;
        if (nearBottom) {
          list.lastElementChild?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }
    }

    async function syncPostRealtime() {
      if (realtimeBusy || document.visibilityState === 'hidden') return;
      realtimeBusy = true;

      try {
        const res = await fetch(
          'post_realtime.php?post_id=' + encodeURIComponent(postId) + '&_=' + Date.now(),
          {
            method: 'GET',
            cache: 'no-store',
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
          }
        );

        if (!res.ok) return;

        const data = await res.json();
        if (!data.ok) return;

        updateLikeUI(data.liked, data.like_count);
        updateCommentCount(data.comment_count);

        // Chỉ dựng lại comment khi dữ liệu thật sự thay đổi.
        if (data.version !== lastRealtimeVersion) {
          syncComments(data.comments || []);
          lastRealtimeVersion = data.version || '';
        }

        window.latestCommentId = Number(data.latest_comment_id || 0);
      } catch (error) {
        console.debug('Realtime sync:', error);
      } finally {
        realtimeBusy = false;
      }
    }

    // Đồng bộ ngay sau khi trang load.
    syncPostRealtime();

    // 1 giây/lần: gần real-time nhưng vẫn nhẹ hơn WebSocket.
    window.postRealtimeTimer = setInterval(syncPostRealtime, 1000);

    // Quay lại tab là đồng bộ ngay.
    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') syncPostRealtime();
    });

    // Cập nhật chữ "x phút trước" mà không cần gọi server.
    window.postRealtimeTimeTimer = setInterval(() => {
      document.querySelectorAll('.fb-comment').forEach(el => {
        // Chỉ cập nhật thời gian nếu server đã lưu timestamp vào data-time.
        const time = el.querySelector('.fb-comment-time');
        if (time && el.dataset.createdAt) {
          time.textContent = formatTimeAgo(el.dataset.createdAt);
        }
      });
    }, 15000);
  }

});

// ---------- Profile avatar dropdown ----------
document.addEventListener('DOMContentLoaded', () => {
  const btn = document.getElementById('profileMenuBtn');
  const menu = document.getElementById('profileMenu');
  if (!btn || !menu) return;

  function closeMenu() {
    menu.hidden = true;
    btn.setAttribute('aria-expanded', 'false');
  }

  btn.addEventListener('click', (event) => {
    event.stopPropagation();
    const willOpen = menu.hidden;
    menu.hidden = !willOpen;
    btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
  });

  menu.addEventListener('click', (event) => event.stopPropagation());
  document.addEventListener('click', closeMenu);
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeMenu();
  });
});


// ---------- Responsive mobile menu + tables ----------
document.addEventListener('DOMContentLoaded', () => {
  // Hamburger menu trên điện thoại/tablet nhỏ.
  const menuBtn = document.getElementById('mobileMenuToggle');
  const mainNav = document.getElementById('mainNav');

  if (menuBtn && mainNav) {
    const closeMobileMenu = () => {
      mainNav.classList.remove('mobile-open');
      menuBtn.setAttribute('aria-expanded', 'false');
      menuBtn.innerHTML = '<i class="fa-solid fa-bars"></i>';
    };

    menuBtn.addEventListener('click', (event) => {
      event.stopPropagation();
      const opened = mainNav.classList.toggle('mobile-open');
      menuBtn.setAttribute('aria-expanded', opened ? 'true' : 'false');
      menuBtn.innerHTML = opened
        ? '<i class="fa-solid fa-xmark"></i>'
        : '<i class="fa-solid fa-bars"></i>';
    });

    document.addEventListener('click', (event) => {
      if (window.innerWidth > 700) return;
      if (!mainNav.contains(event.target) && !menuBtn.contains(event.target)) {
        closeMobileMenu();
      }
    });

    window.addEventListener('resize', () => {
      if (window.innerWidth > 700) closeMobileMenu();
    });
  }

  // Gắn tên cột vào từng ô để bảng có thể chuyển thành card trên mobile.
  document.querySelectorAll('table').forEach(table => {
    const headers = Array.from(table.querySelectorAll('thead th')).map(th =>
      th.textContent.trim()
    );

    if (!headers.length) {
      const firstRow = table.querySelector('tr');
      if (firstRow) {
        firstRow.querySelectorAll('th').forEach(th => headers.push(th.textContent.trim()));
      }
    }

    table.querySelectorAll('tbody tr').forEach(row => {
      row.querySelectorAll('td').forEach((cell, index) => {
        if (headers[index]) cell.setAttribute('data-label', headers[index]);
      });
    });
  });
});
