<?php
require_once '../config/db.php';

$pageTitle = 'Về chúng tôi - BookShop';
include '../includes/header.php';
?>

<style>
    .about-page {
        --about-reference-accent: rgb(0, 169, 242);
    }

    /* ==========================================================================
       1. THANH ĐIỀU HƯỚNG (BREADCRUMB) NỀN XÁM
       ========================================================================== */
    .about-page .breadcrumb-bar {
        background-color: #f5f5f5;
        padding: 12px var(--spacing-md);
        border-bottom: 1px solid #e0e0e0;
    }
    .about-page .breadcrumb-container {
        max-width: 1200px;
        margin: 0 auto;
        font-size: 0.9rem;
        color: #666;
    }
    .about-page .breadcrumb-container a {
        color: #333;
        text-decoration: none;
        transition: color 0.2s;
    }
    .about-page .breadcrumb-container a:hover {
        color: var(--about-reference-accent);
    }
    .about-page .breadcrumb-container span {
        color: var(--about-reference-accent);
    }

    /* ==========================================================================
       2. BỐ CỤC BÀI VIẾT CHÍNH
       ========================================================================== */
    .about-page .about-wrapper {
        max-width: 900px;
        margin: 30px auto 60px auto;
        padding: 0 var(--spacing-md);
        font-family: "Times New Roman", Times, serif;
        color: #111; /* Đen đậm chuẩn báo chí */
    }

    .about-page .about-title {
        font-size: 2.2rem;
        font-weight: 700;
        margin: 0 0 5px 0;
        color: #222;
    }

    .about-page .about-date {
        font-size: 0.9rem;
        color: #888;
        margin-bottom: 40px;
    }

    /* Khối Slogan nổi bật giữa trang */
    .about-page .about-slogan-box {
        text-align: center;
        margin-bottom: 30px;
    }
    .about-page .about-slogan-box h2 {
        color: var(--about-reference-accent);
        font-size: 1.3rem;
        font-weight: bold;
        text-transform: uppercase;
        margin: 0 0 5px 0;
        letter-spacing: 0.5px;
    }
    .about-page .about-slogan-box p {
        font-style: italic;
        color: #444;
        font-size: 1.05rem;
        margin: 0;
    }

    /* Nội dung văn bản */
    .about-page .about-text p {
        font-size: 1.05rem;
        line-height: 1.7;
        text-align: justify;
        margin-bottom: 18px;
    }

    /* ==========================================================================
       3. KHỐI "CÁC DÒNG SẢN PHẨM" MÔ PHỎNG ALPHA BOOKS
       ========================================================================== */
    .about-page .why-bookshop-section { margin-top: 56px; padding-top: 36px; border-top: 1px solid var(--color-border); }
    .about-page .why-bookshop-title { margin: 0 0 26px; color: var(--color-text); font-size: 1.8rem; text-align: center; }
    .about-page .why-bookshop-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 18px; }
    .about-page .why-bookshop-card { padding: 22px 18px; background: var(--color-surface); border: 1px solid var(--color-border); border-radius: 12px; text-align: center; box-shadow: 0 4px 14px rgba(15,23,42,.06); }
    .about-page .why-bookshop-card i { display: inline-grid; place-items: center; width: 42px; height: 42px; margin-bottom: 12px; border-radius: 50%; background: rgba(0,169,242,.12); color: var(--about-reference-accent); font-size: 1.1rem; }
    .about-page .why-bookshop-card h3 { margin: 0 0 8px; color: var(--color-text); font-size: 1.05rem; }
    .about-page .why-bookshop-card p { margin: 0; color: var(--color-text-light); font-size: .92rem; line-height: 1.55; }
    @media (max-width: 768px) {
        .about-page .about-wrapper {
            margin-top: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }

        .about-page .about-title {
            font-size: var(--font-size-xxl);
        }

        .about-page .about-date {
            margin-bottom: var(--spacing-lg);
        }

        .about-page .why-bookshop-section {
            margin-top: var(--spacing-xl);
            padding-top: var(--spacing-xl);
        }

        .about-page .why-bookshop-title {
            margin-bottom: var(--spacing-lg);
            font-size: var(--font-size-xl);
        }

        .about-page .why-bookshop-grid {
            grid-template-columns: 1fr;
            gap: var(--spacing-xl);
        }
    }

    @media (max-width: 430px) {
        .about-page .breadcrumb-container,
        .about-page .about-text p {
            font-size: var(--font-size-sm);
        }

        .about-page .about-text p {
            text-align: left;
        }

        .about-page .why-bookshop-section {
            padding-top: var(--spacing-lg);
        }
    }
</style>

<div class="about-page">
<div class="breadcrumb-bar">
    <div class="breadcrumb-container">
        <a href="<?= url('trangchu/index.php') ?>">Trang chủ</a> &rsaquo; <span>Về chúng tôi</span>
    </div>
</div>

<main class="about-wrapper">
    
    <h1 class="about-title">Về Chúng Tôi</h1>
    <div class="about-date">02/09/2026</div> <div class="about-slogan-box">
        <h2>BookShop - KHÁM PHÁ NHỮNG CUỐN SÁCH DÀNH CHO BẠN</h2>
        <p>Better Knowledge, Better Success</p>
    </div>

    <div class="about-text">
        <p><strong>BookShop</strong> mang đến những lựa chọn sách đa dạng cho nhiều nhu cầu đọc: từ văn học Việt Nam, văn học nước ngoài, tiểu thuyết và các tác phẩm kinh điển, đến tâm lý - kỹ năng sống, kinh tế, thiếu nhi cùng những chủ đề khám phá khác.</p>
        
        <p>Trong catalog hiện tại, bạn có thể tìm thấy những câu chuyện và góc nhìn phong phú qua <em>Cây Cam Ngọt Của Tôi</em>, <em>Nhà Giả Kim (Tái Bản 2020)</em>, <em>Thao Túng Tâm Lý</em>, <em>Hoàng Tử Bé (Tái Bản 2019)</em> và nhiều tựa sách thuộc các thể loại khác nhau.</p>

        <p>Từ <em>Những Người Khốn Khổ (Boxet 2 Tập)</em>, <em>Dám Bị Ghét</em> đến <em>Xứ Cát</em>, mỗi tựa sách tại BookShop mở ra một hành trình riêng để đọc, suy ngẫm và khám phá. Chúng tôi hướng đến trải nghiệm mua sắm thuận tiện, thông tin rõ ràng và những lựa chọn phù hợp cho từng độc giả.</p>
    </div>

    <section class="why-bookshop-section" aria-labelledby="why-bookshop-title">
        <h2 id="why-bookshop-title" class="why-bookshop-title">Vì sao chọn BookShop?</h2>
        <div class="why-bookshop-grid">
            <article class="why-bookshop-card"><i class="fa-solid fa-book-open" aria-hidden="true"></i><h3>Sách được chọn lọc</h3><p>Khám phá nhiều thể loại sách phù hợp cho học tập, công việc và đời sống.</p></article>
            <article class="why-bookshop-card"><i class="fa-solid fa-circle-info" aria-hidden="true"></i><h3>Thông tin rõ ràng</h3><p>Dễ dàng xem thông tin, hình ảnh và tình trạng của từng sản phẩm.</p></article>
            <article class="why-bookshop-card"><i class="fa-solid fa-truck-fast" aria-hidden="true"></i><h3>Đặt hàng thuận tiện</h3><p>Chọn sách, thêm vào giỏ và theo dõi đơn hàng ngay trên BookShop.</p></article>
        </div>
    </section>
</main>
</div>

<?php include '../includes/footer.php'; ?>
