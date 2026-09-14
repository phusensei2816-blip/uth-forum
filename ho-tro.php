<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Hỗ trợ - UTH Forum';
require __DIR__ . '/includes/header.php';
?>
<div class="row" style="gap:24px;">
  <section style="flex:2;min-width:0;">
    <div class="box">
      <h2>Câu hỏi thường gặp</h2>
      <div style="display:flex;flex-direction:column;gap:18px;margin-top:12px;">
        <div>
          <h3 style="margin:0 0 6px;font-size:1rem;">Làm sao để đăng bài lên diễn đàn?</h3>
          <p style="margin:0;color:var(--muted);">Đăng nhập bằng tài khoản sinh viên hoặc giảng viên, sau đó bấm vào avatar ở góc phải trên cùng và chọn "Đăng bài" (hoặc "Đăng thông báo" với giảng viên).</p>
        </div>
        <div>
          <h3 style="margin:0 0 6px;font-size:1rem;">Vì sao bài viết của tôi chưa hiển thị?</h3>
          <p style="margin:0;color:var(--muted);">Bài viết mới cần được Admin kiểm duyệt trước khi hiển thị công khai trên diễn đàn, nhằm đảm bảo nội dung phù hợp. Thời gian duyệt thường trong vòng 24 giờ.</p>
        </div>
        <div>
          <h3 style="margin:0 0 6px;font-size:1rem;">Quên mật khẩu thì phải làm sao?</h3>
          <p style="margin:0;color:var(--muted);">Hiện hệ thống chưa hỗ trợ khôi phục mật khẩu tự động. Vui lòng liên hệ trực tiếp giảng viên phụ trách hoặc quản trị viên diễn đàn để được cấp lại.</p>
        </div>
        <div>
          <h3 style="margin:0 0 6px;font-size:1rem;">Làm sao để tham gia một lớp học?</h3>
          <p style="margin:0;color:var(--muted);">Vào mục "Học tập" trên thanh menu, nhập mã lớp do giảng viên cung cấp để tham gia lớp học phần.</p>
        </div>
      </div>
    </div>
  </section>
  <aside style="flex:1;min-width:260px;">
    <div class="box">
      <h3>Cần hỗ trợ thêm?</h3>
      <p style="color:var(--muted);font-size:14px;">Nếu câu hỏi của bạn chưa có trong danh sách trên, hãy liên hệ trực tiếp giảng viên phụ trách lớp hoặc quản trị viên diễn đàn để được hỗ trợ nhanh nhất.</p>
    </div>
  </aside>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
