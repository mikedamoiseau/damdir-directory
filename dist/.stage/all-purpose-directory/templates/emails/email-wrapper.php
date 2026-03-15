<?php
/**
 * Email wrapper template.
 *
 * This template wraps all email content in a consistent HTML structure.
 * Override this template in your theme: all-purpose-directory/emails/email-wrapper.php
 *
 * @package All_Purpose_Directory
 * @since   1.0.0
 *
 * @var string $email_content The email body content to wrap.
 * @var array  $args          Template arguments.
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$site_name = get_bloginfo( 'name' );
$site_url  = home_url();
$year      = gmdate( 'Y' );

/**
 * Filter the email header background color.
 *
 * @since 1.0.0
 * @param string $color Hex color code.
 */
$header_color = apply_filters( 'apd_email_header_color', '#0073aa' );

/**
 * Filter the email header text color.
 *
 * @since 1.0.0
 * @param string $color Hex color code.
 */
$header_text_color = apply_filters( 'apd_email_header_text_color', '#ffffff' );

/**
 * Filter the email button color.
 *
 * @since 1.0.0
 * @param string $color Hex color code.
 */
$button_color = apply_filters( 'apd_email_button_color', '#0073aa' );
?>
<!DOCTYPE html>
<html lang="<?php echo esc_attr( get_locale() ); ?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="x-apple-disable-message-reformatting">
	<title><?php echo esc_html( $site_name ); ?></title>
	<!--[if mso]>
	<noscript>
		<xml>
			<o:OfficeDocumentSettings>
				<o:PixelsPerInch>96</o:PixelsPerInch>
			</o:OfficeDocumentSettings>
		</xml>
	</noscript>
	<![endif]-->
	<style type="text/css">
		/* Reset styles */
		body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
		table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
		img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }

		/* Base styles */
		body {
			margin: 0 !important;
			padding: 0 !important;
			font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
			font-size: 16px;
			line-height: 1.6;
			color: #333333;
			background-color: #f5f5f5;
			width: 100% !important;
			height: 100% !important;
		}

		/* Email wrapper */
		.email-wrapper {
			width: 100%;
			max-width: 600px;
			margin: 0 auto;
			padding: 20px;
		}

		/* Header */
		.email-header {
			background-color: <?php echo esc_attr( $header_color ); ?>;
			color: <?php echo esc_attr( $header_text_color ); ?>;
			padding: 25px 30px;
			text-align: center;
			border-radius: 8px 8px 0 0;
		}

		.email-header h1 {
			margin: 0;
			font-size: 24px;
			font-weight: 600;
			color: <?php echo esc_attr( $header_text_color ); ?>;
		}

		.email-header a {
			color: <?php echo esc_attr( $header_text_color ); ?>;
			text-decoration: none;
		}

		/* Body */
		.email-body {
			background-color: #ffffff;
			padding: 30px;
			border-left: 1px solid #e0e0e0;
			border-right: 1px solid #e0e0e0;
		}

		.email-body h2 {
			margin: 0 0 20px;
			font-size: 20px;
			font-weight: 600;
			color: #333333;
		}

		.email-body p {
			margin: 0 0 15px;
			font-size: 16px;
			line-height: 1.6;
			color: #555555;
		}

		.email-body a {
			color: <?php echo esc_attr( $button_color ); ?>;
		}

		/* Info table */
		.info-table {
			width: 100%;
			border-collapse: collapse;
			margin: 20px 0;
		}

		.info-table td {
			padding: 10px 12px;
			border-bottom: 1px solid #eeeeee;
			font-size: 14px;
		}

		.info-table td:first-child {
			font-weight: 600;
			color: #333333;
			width: 35%;
		}

		.info-table td:last-child {
			color: #555555;
		}

		/* Button */
		.button {
			display: inline-block;
			padding: 14px 28px;
			background-color: <?php echo esc_attr( $button_color ); ?>;
			color: #ffffff !important;
			text-decoration: none;
			border-radius: 6px;
			font-size: 16px;
			font-weight: 500;
			margin: 10px 0;
		}

		.button:hover {
			background-color: #005a87;
		}

		.button-secondary {
			background-color: #6c757d;
		}

		.button-secondary:hover {
			background-color: #545b62;
		}

		/* Footer */
		.email-footer {
			background-color: #f9f9f9;
			padding: 20px 30px;
			text-align: center;
			font-size: 13px;
			color: #888888;
			border: 1px solid #e0e0e0;
			border-top: none;
			border-radius: 0 0 8px 8px;
		}

		.email-footer a {
			color: <?php echo esc_attr( $button_color ); ?>;
			text-decoration: none;
		}

		.email-footer p {
			margin: 5px 0;
		}

		/* Utility classes */
		.text-center { text-align: center; }
		.text-muted { color: #888888; font-size: 14px; }
		.mb-0 { margin-bottom: 0 !important; }
		.mb-20 { margin-bottom: 20px !important; }
		.mt-20 { margin-top: 20px !important; }

		/* Responsive */
		@media only screen and (max-width: 600px) {
			.email-wrapper {
				padding: 10px !important;
			}
			.email-header,
			.email-body,
			.email-footer {
				padding: 20px !important;
			}
			.button {
				display: block;
				width: 100%;
				text-align: center;
				box-sizing: border-box;
			}
		}
	</style>
</head>
<body>
	<table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f5f5f5;">
		<tr>
			<td align="center" style="padding: 20px 0;">
				<table role="presentation" class="email-wrapper" cellpadding="0" cellspacing="0" width="600" style="max-width: 600px;">
					<!-- Header -->
					<tr>
						<td class="email-header">
							<h1>
								<a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_name ); ?></a>
							</h1>
						</td>
					</tr>
					<!-- Body -->
					<tr>
						<td class="email-body">
							<?php
							// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo $email_content;
							?>
						</td>
					</tr>
					<!-- Footer -->
					<tr>
						<td class="email-footer">
							<p>&copy; <?php echo esc_html( $year ); ?> <a href="<?php echo esc_url( $site_url ); ?>"><?php echo esc_html( $site_name ); ?></a></p>
							<p class="text-muted"><?php esc_html_e( 'This is an automated message. Please do not reply directly to this email.', 'all-purpose-directory' ); ?></p>
						</td>
					</tr>
				</table>
			</td>
		</tr>
	</table>
</body>
</html>
