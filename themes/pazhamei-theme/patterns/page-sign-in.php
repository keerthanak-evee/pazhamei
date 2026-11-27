<?php
/**
 * Title: Sign in: Sign in, Register and Forgot password
 * Slug: pazhamei/page-sign-in
 * Categories: pazhamei-pages
 * Description: The sign-in page from Login&RegisterPAgeReference.jpg. A wine panel and a white form card for Sign in, the mirror of it for Register, a card for Forgot password, and the account pages. The page shows one view at a time.
 * Viewport Width: 1400
 *
 * Each view is a group with the class pz-auth__view--<view>. The plugin adds
 * the body class pz-auth-view--<view> (sign-in, register, lost-password,
 * account) and the theme shows only that group; in the editor all four show.
 * The forms themselves are the pz/account-form block.
 *
 * @package pazhamei-theme
 */

$pz_emblem  = esc_url( get_theme_file_uri( 'assets/images/emblem.svg' ) );
$pz_sign_in = esc_url( home_url( '/sign-in/' ) );
$pz_join    = esc_url( home_url( '/sign-in/?view=register' ) );

// Logo row shown at the top of both wine panels.
$pz_logo = '<!-- wp:group {"className":"pz-auth__logo","layout":{"type":"flex","flexWrap":"nowrap","justifyContent":"center"}} -->
<div class="wp-block-group pz-auth__logo"><!-- wp:image {"sizeSlug":"full","linkDestination":"none","className":"pz-auth__emblem"} -->
<figure class="wp-block-image size-full pz-auth__emblem"><img src="' . $pz_emblem . '" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:group {"className":"pz-auth__brand","layout":{"type":"default"}} -->
<div class="wp-block-group pz-auth__brand"><!-- wp:paragraph {"className":"pz-auth__wordmark"} -->
<p class="pz-auth__wordmark">PAZHAMEI</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"pz-auth__tagline"} -->
<p class="pz-auth__tagline">Fuel meals · Rooted in good food</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->';

$pz_photo = pz_theme_image_block( 'signin-thali', 'pz-auth__photo' );
?>
<!-- wp:group {"align":"full","className":"pz-auth pz-kolam-corners","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60","left":"var:preset|spacing|40","right":"var:preset|spacing|40"}}},"backgroundColor":"parchment","layout":{"type":"default"}} -->
<div class="wp-block-group alignfull pz-auth pz-kolam-corners has-parchment-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-right:var(--wp--preset--spacing--40);padding-bottom:var(--wp--preset--spacing--60);padding-left:var(--wp--preset--spacing--40)"><!-- wp:group {"className":"pz-auth__view pz-auth__view--sign-in","layout":{"type":"default"}} -->
<div class="wp-block-group pz-auth__view pz-auth__view--sign-in"><!-- wp:group {"className":"is-style-pz-auth-panel","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-auth-panel"><?php echo $pz_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from escaped parts. ?>

<?php echo $pz_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:heading {"className":"pz-auth__welcome"} -->
<h2 class="wp-block-heading pz-auth__welcome">Welcome Back to Your Daily Ritual</h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"pz-checklist pz-auth__points"} -->
<ul class="wp-block-list pz-checklist pz-auth__points"><!-- wp:list-item -->
<li>Freshly cooked in our Guindy kitchen</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Macro-calibrated lunches</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>8:00 PM flexible cutoff</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-pz-auth-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-auth-card"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Sign in to your account</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"pz-auth__intro"} -->
<p class="pz-auth__intro">Welcome back! Enter your details to access your account.</p>
<!-- /wp:paragraph -->

<!-- wp:pz/account-form {"mode":"sign-in"} /-->

<!-- wp:paragraph {"className":"pz-auth__switch"} -->
<p class="pz-auth__switch">Don’t have an account? <a href="<?php echo $pz_join; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>"><strong>Create Account</strong></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"pz-auth__view pz-auth__view--register","layout":{"type":"default"}} -->
<div class="wp-block-group pz-auth__view pz-auth__view--register"><!-- wp:group {"className":"is-style-pz-auth-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-auth-card"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Create your account</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"pz-auth__intro"} -->
<p class="pz-auth__intro">Join us! Fill in your details to start your journey.</p>
<!-- /wp:paragraph -->

<!-- wp:pz/account-form {"mode":"register"} /-->

<!-- wp:paragraph {"className":"pz-auth__switch"} -->
<p class="pz-auth__switch">Already have an account? <a href="<?php echo $pz_sign_in; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>"><strong>Sign In</strong></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"is-style-pz-auth-panel","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-auth-panel"><?php echo $pz_logo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built above from escaped parts. ?>

<?php echo $pz_photo; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:heading {"className":"pz-auth__welcome"} -->
<h2 class="wp-block-heading pz-auth__welcome">Welcome to PAZHAMEI</h2>
<!-- /wp:heading --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"pz-auth__view pz-auth__view--lost-password","layout":{"type":"default"}} -->
<div class="wp-block-group pz-auth__view pz-auth__view--lost-password"><!-- wp:group {"className":"is-style-pz-auth-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-auth-card"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Reset your password</h1>
<!-- /wp:heading -->

<!-- wp:pz/account-form {"mode":"lost-password"} /-->

<!-- wp:paragraph {"className":"pz-auth__switch"} -->
<p class="pz-auth__switch">Remembered it? <a href="<?php echo $pz_sign_in; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped above. ?>"><strong>Sign In</strong></a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"pz-auth__view pz-auth__view--account","layout":{"type":"default"}} -->
<div class="wp-block-group pz-auth__view pz-auth__view--account"><!-- wp:group {"className":"is-style-pz-auth-card","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-auth-card"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Your account</h1>
<!-- /wp:heading -->

<!-- wp:pz/account-form {"mode":"account"} /--></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
