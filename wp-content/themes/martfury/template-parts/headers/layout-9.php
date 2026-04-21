<div class="header-main">
    <div class="<?php echo martfury_header_container_classes(); ?>">
        <div class="row header-row">
            <div class="header-logo col-lg-3 col-md-3 col-sm-6 col-xs-6">
                <div class="d-logo">
					<?php get_template_part( 'template-parts/logo' ); ?>
                </div>
				<?php if ( intval( martfury_get_option( 'sticky_header' ) ) ) : ?>
                    <div class="d-department hidden-xs hidden-sm">
						<?php martfury_extra_department( false ); ?>
                    </div>
				<?php endif; ?>
            </div>
            <div class="header-extras col-lg-9 col-md-9 col-sm-6 col-xs-6">
				<?php martfury_extra_search(); ?>
                <ul class="extras-menu">
					<?php
					martfury_extra_hotline();
					martfury_extra_compare();
					martfury_extra_cart();
					martfury_extra_account();
					?>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="main-menu hidden-xs hidden-sm">
    <div class="<?php echo martfury_header_container_classes(); ?>">
        <div class="row header-row">
            <div class="col-lg-8 col-header-menu">
                <?php martfury_header_menu(); ?>
            </div>
            <div class="col-lg-4 col-header-extras hidden-xs hidden-sm hidden-md">
                <div class="recently-viewed">
                    <?php martfury_header_recently_products(); ?>
                </div>
                <ul class="header-wishlist">
					<?php martfury_extra_wislist(); ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<div class="mobile-menu hidden-lg hidden-md">
    <div class="container">
        <div class="mobile-menu-row">
            <a class="mf-toggle-menu" id="mf-toggle-menu" href="#">
                <i class="icon-menu"></i>
            </a>
			<?php martfury_extra_search( false ); ?>
        </div>
    </div>
</div>
