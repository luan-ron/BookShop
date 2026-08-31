<?php
/** @var string $pageTitle Tiêu đề trang  */
$footer_categories = [];

if (isset($global_categories) && is_array($global_categories)) {
    $footer_categories = $global_categories;
} elseif (isset($conn)) {
    $stmt_footer_categories = $conn->prepare(
        "SELECT CategoryID, CategoryName FROM category ORDER BY CategoryID ASC"
    );
    if ($stmt_footer_categories) {
        $stmt_footer_categories->execute();
        $result_footer_categories = $stmt_footer_categories->get_result();
        while ($category = $result_footer_categories->fetch_assoc()) {
            $footer_categories[] = $category;
        }
        $result_footer_categories->free();
        $stmt_footer_categories->close();
    }
}
?>
<footer class="footer">
    <div class="footer__grid">

        <!-- Cột 1: Thương hiệu -->
        <div>
            <div class="footer__brand">BookShop</div>
            <p class="footer__desc">Khám phá những cuốn sách được chọn lọc để học tập, làm việc và mở rộng thế giới của bạn.
            </p>
            <div class="footer__social">
                <a href="#" class="footer__social-link" aria-label="Facebook chưa cấu hình liên kết">
                    <i class="fa-brands fa-facebook-f" aria-hidden="true"></i>
                </a>
                <a href="#" class="footer__social-link" aria-label="Zalo chưa cấu hình liên kết">
                    <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
                </a>
                <a href="#" class="footer__social-link" aria-label="GitHub chưa cấu hình liên kết">
                    <i class="fa-brands fa-github" aria-hidden="true"></i>
                </a>
            </div>
        </div>

        <!-- Cột 2: Danh mục -->
        <div>
            <div class="footer__title">Danh mục</div>
            <ul class="footer__links footer__category-links">
                <?php foreach ($footer_categories as $category): ?>
                    <li>
                        <a href="<?= url('trangchu/category.php?id=' . (int) $category['CategoryID']) ?>" class="footer__link">
                            <?= htmlspecialchars($category['CategoryName']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Cột 3: Hỗ trợ -->
        <div>
            <div class="footer__title">Hỗ trợ</div>
            <ul class="footer__links">
                <li><a href="#" class="footer__link">Hướng dẫn mua hàng</a></li>
                <li><a href="#" class="footer__link">Chính sách đổi trả</a></li>
                <li><a href="#" class="footer__link">Chính sách bảo mật</a></li>
                <li><a href="#" class="footer__link">Câu hỏi thường gặp</a></li>
            </ul>
        </div>

        <!-- Cột 4: Liên hệ -->
        <div>
            <div class="footer__title">Liên hệ</div>
            <ul class="footer__contact-list">
                <li class="footer__contact-item">
                    <i class="fa-solid fa-location-dot footer__contact-icon" aria-hidden="true"></i>
                    <span>Số 2, đường Võ Oanh, P. Thạnh Mỹ Tây, TP. HCM</span>
                </li>
                <li class="footer__contact-item">
                    <i class="fa-solid fa-phone footer__contact-icon" aria-hidden="true"></i>
                    <a class="footer__contact-link" href="tel:0909123456">0123 456 789</a>
                </li>
                <li class="footer__contact-item">
                    <i class="fa-solid fa-envelope footer__contact-icon" aria-hidden="true"></i>
                    <a class="footer__contact-link" href="mailto:hello@bookshop.vn">web@bookshop.vn</a>
                </li>
            </ul>
        </div>

    </div>

    <div class="footer__bottom">
        <span>© <?= date('Y') ?> BookShop. All rights reserved.</span>
        <span>Thiết kế bởi nhóm BookShop</span>
    </div>
</footer><div class="bookshop-chatbot" data-chatbot><button type="button" class="bookshop-chatbot__toggle" data-chatbot-toggle aria-expanded="false" aria-controls="bookshop-chatbot-panel" aria-label="Mở hỗ trợ BookShop"><i class="fa-solid fa-comments" aria-hidden="true"></i></button><section class="bookshop-chatbot__panel" id="bookshop-chatbot-panel" data-chatbot-panel hidden aria-label="Hỗ trợ BookShop"><div class="bookshop-chatbot__header"><span><i class="fa-solid fa-robot" aria-hidden="true"></i> Hỗ trợ BookShop</span><button type="button" data-chatbot-close aria-label="Đóng hỗ trợ"><i class="fa-solid fa-xmark" aria-hidden="true"></i></button></div><div class="bookshop-chatbot__messages" aria-live="polite"><p class="bookshop-chatbot__message bookshop-chatbot__message--bot">Xin chào! BookShop có thể hỗ trợ bạn tìm sách.</p><p class="bookshop-chatbot__message bookshop-chatbot__message--user">Mình muốn tìm một cuốn sách hay.</p><p class="bookshop-chatbot__message bookshop-chatbot__message--bot">Bạn có thể xem các danh mục nổi bật trên cửa hàng nhé!</p></div><form class="bookshop-chatbot__form" data-chatbot-form><input type="text" placeholder="Nhập tin nhắn..." aria-label="Tin nhắn hỗ trợ"><button type="submit" aria-label="Gửi tin nhắn"><i class="fa-solid fa-paper-plane" aria-hidden="true"></i></button></form></section></div><style>.bookshop-chatbot{position:fixed;right:24px;bottom:24px;z-index:1000;font-family:var(--font-family-base)}.bookshop-chatbot__toggle{width:52px;height:52px;border:0;border-radius:50%;background:var(--color-primary);color:#fff;box-shadow:0 8px 22px rgba(15,23,42,.2);cursor:pointer;font-size:20px}.bookshop-chatbot__toggle:hover,.bookshop-chatbot__toggle:focus-visible{background:var(--color-primary-hover);transform:translateY(-2px)}.bookshop-chatbot__panel{position:absolute;right:0;bottom:64px;width:min(350px,calc(100vw - 32px));overflow:hidden;border:1px solid var(--color-border);border-radius:16px;background:#fff;box-shadow:0 16px 40px rgba(15,23,42,.2)}.bookshop-chatbot__header{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--color-primary);color:#fff;font-weight:700}.bookshop-chatbot__header button{border:0;background:transparent;color:inherit;cursor:pointer;font-size:18px}.bookshop-chatbot__messages{display:grid;gap:10px;padding:16px;background:var(--color-background)}.bookshop-chatbot__message{max-width:84%;margin:0;padding:9px 11px;border-radius:12px;font-size:13px;line-height:1.4}.bookshop-chatbot__message--bot{background:#fff;color:var(--color-text);border:1px solid var(--color-border)}.bookshop-chatbot__message--user{justify-self:end;background:rgba(0,169,242,.12);color:var(--color-text)}.bookshop-chatbot__form{display:flex;gap:8px;padding:12px;border-top:1px solid var(--color-border);background:#fff}.bookshop-chatbot__form input{min-width:0;flex:1;padding:9px 10px;border:1px solid var(--color-border);border-radius:8px;font:inherit}.bookshop-chatbot__form button{width:38px;border:0;border-radius:8px;background:var(--color-primary);color:#fff;cursor:pointer}@media(max-width:600px){.bookshop-chatbot{right:16px;bottom:16px}}</style><script>(function(){const r=document.querySelector('[data-chatbot]');if(!r)return;const t=r.querySelector('[data-chatbot-toggle]'),p=r.querySelector('[data-chatbot-panel]'),c=r.querySelector('[data-chatbot-close]');const o=v=>{p.hidden=!v;t.setAttribute('aria-expanded',String(v))};t.addEventListener('click',()=>o(p.hidden));c.addEventListener('click',()=>o(false));r.querySelector('[data-chatbot-form]').addEventListener('submit',e=>e.preventDefault());document.addEventListener('keydown',e=>{if(e.key==='Escape')o(false)})}());</script>

<script src="https://cdn.jsdelivr.net/npm/toastify-js@1.12.0/src/toastify.min.js"></script>
<script src="/BookShop/assets/js/toast.js?v=<?= time() ?>"></script>
<?php if (isset($_SESSION['log_toast'])): 
    $toastMsg = $_SESSION['log_toast'];
    $toastType = preg_match('/(thất bại|hủy|lỗi|xóa|vượt quá|giới hạn|hết hàng|cảnh báo)/i', $toastMsg) ? 'error' : 'success';
?>
    <script>showToast(<?= json_encode($toastMsg, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($toastType) ?>);</script>
    <?php unset($_SESSION['log_toast']); ?>
<?php endif; ?>
<script src="/BookShop/assets/js/main.js?v=<?= time() ?>"></script>
