<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Lightweight Zero-Dependency SMTP & Transactional Email Service
 * 
 * Sends branded HTML emails via native SMTP (SSL/TLS) with automatic
 * local fallback logger during development or when SMTP is unconfigured.
 */
class Mailer
{
    /**
     * Send welcome email containing login credentials after storefront checkout
     *
     * @param array $tenant
     * @param string $plainPassword
     * @return bool
     */
    public static function sendWelcomeEmail(array $tenant, string $plainPassword): bool
    {
        $to = $tenant['owner_email'];
        $toName = $tenant['owner_name'] ?? 'Store Owner';
        $storeName = $tenant['name'];
        $slug = $tenant['slug'];
        $package = $tenant['package_name'] ?? 'স্ট্যান্ডার্ড প্যাকেজ';
        $fee = $tenant['monthly_fee'] ?? 199;
        $trxId = $tenant['payment_trx_id'] ?? '-';

        // Determine base and portal URL
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $isLive = (strpos($host, 'ponnostack.shop') !== false);
        $portalUrl = $isLive ? 'https://ponnostack.shop/portal/' : ($protocol . $host . '/Ponnostack/portal/');
        $storeUrl = $isLive ? "https://{$slug}.ponnostack.shop" : ($protocol . $host . "/Ponnostack/store/{$slug}");

        $subject = "অভিনন্দন! আপনার Ponnostack স্টোরফ্রন্ট অ্যাকাউন্ট ও ক্রেডেনশিয়াল";

        // Generate clean, high-end HTML email
        $html = self::renderWelcomeTemplate([
            'toName'     => $toName,
            'storeName'  => $storeName,
            'storeUrl'   => $storeUrl,
            'slug'       => $slug,
            'email'      => $to,
            'password'   => $plainPassword,
            'portalUrl'  => $portalUrl,
            'package'    => $package,
            'fee'        => $fee,
            'trxId'      => $trxId,
        ]);

        return self::send($to, $toName, $subject, $html);
    }

    /**
     * Send email via SMTP or fallback to local log
     */
    public static function send(string $to, string $toName, string $subject, string $htmlBody): bool
    {
        $smtpHost = Env::get('MAIL_HOST', '');
        $smtpPort = (int)Env::get('MAIL_PORT', 465);
        $smtpUser = Env::get('MAIL_USERNAME', 'support@ponnostack.shop');
        $smtpPass = Env::get('MAIL_PASSWORD', '');
        $fromEmail = Env::get('MAIL_FROM_ADDRESS', 'support@ponnostack.shop');
        $fromName = Env::get('MAIL_FROM_NAME', 'PonnoStack');

        // If SMTP password is not set (e.g. initial dev), save to local file for preview
        if (empty($smtpPass) || empty($smtpHost)) {
            self::logEmailLocally($to, $subject, $htmlBody);
            return true;
        }

        try {
            $isSsl = ($smtpPort === 465 || Env::get('MAIL_ENCRYPTION') === 'ssl');
            $remote = ($isSsl ? 'ssl://' : 'tcp://') . $smtpHost . ':' . $smtpPort;

            $context = stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ]);

            $socket = @stream_socket_client($remote, $errno, $errstr, 10, STREAM_CLIENT_CONNECT, $context);
            if (!$socket) {
                self::logEmailLocally($to, $subject, $htmlBody, "SMTP Socket Connection Failed: $errstr");
                return false;
            }

            stream_set_timeout($socket, 10);

            // Read welcome greeting
            self::readResponse($socket);

            // EHLO
            self::sendCommand($socket, "EHLO " . ($smtpHost ?: 'localhost'));

            // AUTH LOGIN
            self::sendCommand($socket, "AUTH LOGIN");
            self::sendCommand($socket, base64_encode($smtpUser));
            self::sendCommand($socket, base64_encode($smtpPass));

            // MAIL FROM
            self::sendCommand($socket, "MAIL FROM: <$fromEmail>");

            // RCPT TO
            self::sendCommand($socket, "RCPT TO: <$to>");

            // DATA
            self::sendCommand($socket, "DATA");

            // Build headers
            $boundary = "----=_Part_" . md5((string)microtime(true));
            $headers = [
                "From: =?UTF-8?B?" . base64_encode($fromName) . "?= <$fromEmail>",
                "To: =?UTF-8?B?" . base64_encode($toName) . "?= <$to>",
                "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
                "MIME-Version: 1.0",
                "Content-Type: text/html; charset=UTF-8",
                "Content-Transfer-Encoding: base64",
                "X-Mailer: Ponnostack-Mailer/1.0",
                "Date: " . date('r'),
            ];

            $messageData = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($htmlBody)) . "\r\n.";
            self::sendCommand($socket, $messageData);

            // QUIT
            self::sendCommand($socket, "QUIT");
            fclose($socket);

            self::logEmailLocally($to, $subject, $htmlBody, "Sent via live SMTP ($smtpHost)");
            return true;
        } catch (\Throwable $e) {
            self::logEmailLocally($to, $subject, $htmlBody, "SMTP Error: " . $e->getMessage());
            return false;
        }
    }

    private static function sendCommand($socket, string $command): string
    {
        fwrite($socket, $command . "\r\n");
        return self::readResponse($socket);
    }

    private static function readResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return $response;
    }

    /**
     * Local storage log so emails can be inspected even without live SMTP
     */
    private static function logEmailLocally(string $to, string $subject, string $html, string $status = 'Logged Locally'): void
    {
        $root = dirname(__DIR__, 3);
        $logDir = $root . '/uploads/email_logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $filename = $logDir . '/email_' . date('Y-m-d_His') . '_' . substr(md5($to), 0, 6) . '.html';
        $meta = "<!-- STATUS: $status | TO: $to | SUBJECT: $subject | DATE: " . date('Y-m-d H:i:s') . " -->\n";
        @file_put_contents($filename, $meta . $html);
    }

    /**
     * Branded Welcome Email HTML Template
     */
    private static function renderWelcomeTemplate(array $d): string
    {
        $toName = htmlspecialchars($d['toName']);
        $storeName = htmlspecialchars($d['storeName']);
        $storeUrl = htmlspecialchars($d['storeUrl']);
        $email = htmlspecialchars($d['email']);
        $password = htmlspecialchars($d['password']);
        $portalUrl = htmlspecialchars($d['portalUrl']);
        $package = htmlspecialchars($d['package']);
        $fee = (int)$d['fee'];
        $trxId = htmlspecialchars($d['trxId']);

        return <<<HTML
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ponnostack Credentials</title>
</head>
<body style="margin: 0; padding: 0; background-color: #F8FAFC; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #1E293B;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color: #F8FAFC; padding: 30px 10px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width: 600px; background-color: #FFFFFF; border-radius: 20px; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 10px 25px rgba(0,0,0,0.03);">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #00684A; padding: 30px 40px; text-align: center;">
                            <h1 style="margin: 0; color: #FFFFFF; font-size: 26px; font-weight: 800; letter-spacing: -0.5px;">Ponno<span style="color: #00ED64;">Stack</span></h1>
                            <p style="margin: 6px 0 0; color: #E6F4EA; font-size: 13px; font-weight: 500;">আপনার ব্র্যান্ডেড ই-কমার্স স্টোরফ্রন্ট হাব</p>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td style="padding: 35px 35px 25px;">
                            <h2 style="margin: 0 0 12px; font-size: 18px; color: #0F172A; font-weight: 700;">অভিনন্দন, {$toName}!</h2>
                            <p style="margin: 0 0 24px; font-size: 14px; color: #475569; line-height: 1.6;">
                                <strong>{$storeName}</strong> সফলভাবে Ponnostack প্ল্যাটফর্মে যুক্ত হয়েছে। আপনি যেকোনো সময় নিচের ক্রেডেনশিয়াল ব্যবহার করে আপনার কাস্টমার পোর্টালে লগইন করতে পারবেন:
                            </p>

                            <!-- Credentials Box -->
                            <table role="presentation" width="100%" style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 14px; padding: 18px; margin-bottom: 25px;">
                                <tr>
                                    <td>
                                        <p style="margin: 0 0 8px; font-size: 11px; text-transform: uppercase; color: #00684A; font-weight: 800; letter-spacing: 0.5px;">🔑 পোর্টাল লগইন ক্রেডেনশিয়াল</p>
                                        <p style="margin: 4px 0; font-size: 14px; color: #1E293B;"><strong>লগইন ইমেইল:</strong> <code style="background: #FFFFFF; padding: 2px 6px; border-radius: 6px; border: 1px solid #DCFCE7; font-family: monospace;">{$email}</code></p>
                                        <p style="margin: 4px 0; font-size: 14px; color: #1E293B;"><strong>পাসওয়ার্ড:</strong> <code style="background: #FFFFFF; padding: 2px 6px; border-radius: 6px; border: 1px solid #DCFCE7; font-family: monospace; font-weight: bold; color: #00523A;">{$password}</code></p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Order Details Box -->
                            <table role="presentation" width="100%" style="background-color: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; padding: 18px; margin-bottom: 28px; font-size: 13px;">
                                <tr>
                                    <td style="padding-bottom: 6px; color: #64748B;">স্টোরের নাম:</td>
                                    <td align="right" style="padding-bottom: 6px; font-weight: 700; color: #0F172A;">{$storeName}</td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 6px; color: #64748B;">স্টোরফ্রন্ট লিংক:</td>
                                    <td align="right" style="padding-bottom: 6px; font-weight: 700; color: #00684A;">{$storeUrl}</td>
                                </tr>
                                <tr>
                                    <td style="padding-bottom: 6px; color: #64748B;">প্যাকেজ:</td>
                                    <td align="right" style="padding-bottom: 6px; font-weight: 700; color: #0F172A;">{$package} (৳ {$fee}/মাস)</td>
                                </tr>
                                <tr>
                                    <td style="color: #64748B;">বিকাশ TrxID:</td>
                                    <td align="right" style="font-weight: 700; font-family: monospace; color: #0F172A;">{$trxId}</td>
                                </tr>
                            </table>

                            <!-- CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom: 25px;">
                                <tr>
                                    <td align="center">
                                        <a href="{$portalUrl}" target="_blank" style="display: inline-block; background-color: #00684A; color: #FFFFFF; text-decoration: none; font-size: 14px; font-weight: 700; padding: 13px 32px; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 104, 74, 0.25);">কাস্টমার পোর্টালে প্রবেশ করুন &rarr;</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin: 0; font-size: 12px; color: #94A3B8; line-height: 1.5; text-align: center;">
                                💡 আপনার বিকাশ পেমেন্টটি বর্তমানে ভেরিফিকেশনে রয়েছে। সুপার অ্যাডমিন অনুমোদন করলেই আপনার স্টোরফ্রন্ট স্বয়ংক্রিয়ভাবে লাইভ হবে।
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F1F5F9; padding: 20px; text-align: center; border-top: 1px solid #E2E8F0;">
                            <p style="margin: 0 0 4px; font-size: 12px; font-weight: bold; color: #475569;">Ponnostack Cloud Commerce Platform</p>
                            <p style="margin: 0; font-size: 11px; color: #94A3B8;">সহায়তার জন্য যোগাযোগ করুন: <a href="mailto:support@ponnostack.shop" style="color: #00684A; text-decoration: none;">support@ponnostack.shop</a></p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;
    }

    /**
     * Send order confirmation/notification email
     *
     * @param array $order
     * @param array $tenant
     * @return bool
     */
    public static function sendOrderNotificationEmail(array $order, array $tenant): bool
    {
        $storeName = $tenant['name'] ?? 'PonnoStack Store';
        $orderNum = $order['order_number'] ?? 'ORD-0000';
        $customerName = $order['customer_name'] ?? 'Customer';
        $customerPhone = $order['customer_phone'] ?? '';
        $customerEmail = !empty($order['customer_email']) ? trim($order['customer_email']) : '';
        $totalAmount = number_format((float)($order['total_amount'] ?? 0), 2);
        $deliveryCharge = number_format((float)($order['delivery_charge'] ?? 0), 2);
        $subtotal = number_format((float)($order['subtotal'] ?? 0), 2);
        $address = "{$order['delivery_address']}, {$order['upazilla']}, {$order['district']}, {$order['division']}";

        // Build item rows HTML
        $itemsHtml = '';
        if (!empty($order['items']) && is_array($order['items'])) {
            foreach ($order['items'] as $item) {
                $pName = htmlspecialchars($item['product_name'] ?? 'পণ্য');
                $variantParts = [];
                if (!empty($item['size'])) $variantParts[] = "সাইজ: " . htmlspecialchars($item['size']);
                if (!empty($item['color'])) $variantParts[] = "রঙ: " . htmlspecialchars($item['color']);
                $variantDesc = !empty($variantParts) ? (' (' . implode(', ', $variantParts) . ')') : '';
                $uPrice = number_format((float)($item['unit_price'] ?? 0), 2);
                $qty = (int)($item['quantity'] ?? 1);
                $tPrice = number_format((float)($item['total_price'] ?? ($item['unit_price'] * $qty)), 2);

                $itemsHtml .= "<tr>
                    <td style='padding: 8px 10px; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #1E293B;'>{$pName}{$variantDesc}</td>
                    <td align='center' style='padding: 8px 10px; border-bottom: 1px solid #E2E8F0; font-size: 13px; color: #64748B;'>{$qty}</td>
                    <td align='right' style='padding: 8px 10px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 600; color: #1E293B;'>৳ {$uPrice}</td>
                    <td align='right' style='padding: 8px 10px; border-bottom: 1px solid #E2E8F0; font-size: 13px; font-weight: 700; color: #00684A;'>৳ {$tPrice}</td>
                </tr>";
            }
        }

        $subject = "অর্ডার নিশ্চিতকরণ — #{$orderNum} ({$storeName})";

        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{$subject}</title>
</head>
<body style="margin: 0; padding: 20px; background-color: #F8FAFC; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" width="600" style="max-width: 600px; width: 100%; background-color: #FFFFFF; border-radius: 16px; overflow: hidden; border: 1px solid #E2E8F0;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #00684A; padding: 25px 30px; text-align: center; color: #FFFFFF;">
                            <h1 style="margin: 0; font-size: 20px; font-weight: 800;">{$storeName}</h1>
                            <p style="margin: 6px 0 0; font-size: 13px; opacity: 0.9;">আপনার অর্ডারটি সফলভাবে গ্রহণ করা হয়েছে!</p>
                        </td>
                    </tr>

                    <!-- Body Content -->
                    <tr>
                        <td style="padding: 25px 30px;">
                            <div style="background-color: #F0FDF4; border: 1px solid #BBF7D0; border-radius: 12px; padding: 15px 20px; margin-bottom: 20px; text-align: center;">
                                <span style="font-size: 12px; font-weight: 700; color: #166534; text-transform: uppercase;">ইনভয়েস বা অর্ডার নম্বর</span>
                                <h2 style="margin: 4px 0 0; font-size: 20px; font-family: monospace; font-weight: 800; color: #00684A;">#{$orderNum}</h2>
                            </div>

                            <h3 style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0 0 10px;">ডেলিভারি বিবরণ:</h3>
                            <table role="presentation" width="100%" style="font-size: 13px; margin-bottom: 20px; background-color: #F8FAFC; border-radius: 10px; padding: 12px;">
                                <tr>
                                    <td style="padding: 4px 8px; color: #64748B; width: 30%;">গ্রাহকের নাম:</td>
                                    <td style="padding: 4px 8px; font-weight: 600; color: #0F172A;">{$customerName}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 8px; color: #64748B;">মোবাইল নম্বর:</td>
                                    <td style="padding: 4px 8px; font-weight: 600; color: #0F172A;">{$customerPhone}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 8px; color: #64748B;">ডেলিভারি ঠিকানা:</td>
                                    <td style="padding: 4px 8px; font-weight: 600; color: #0F172A;">{$address}</td>
                                </tr>
                                <tr>
                                    <td style="padding: 4px 8px; color: #64748B;">পেমেন্ট পদ্ধতি:</td>
                                    <td style="padding: 4px 8px; font-weight: 700; color: #16A34A;">ক্যাশ অন ডেলিভারি (Cash on Delivery)</td>
                                </tr>
                            </table>

                            <h3 style="font-size: 14px; font-weight: 700; color: #0F172A; margin: 0 0 10px;">অর্ডারকৃত পণ্যসমূহ:</h3>
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse: collapse; margin-bottom: 20px;">
                                <thead>
                                    <tr style="background-color: #F1F5F9;">
                                        <th align="left" style="padding: 8px 10px; font-size: 12px; color: #475569; font-weight: 700;">পণ্য</th>
                                        <th align="center" style="padding: 8px 10px; font-size: 12px; color: #475569; font-weight: 700;">পরিমাণ</th>
                                        <th align="right" style="padding: 8px 10px; font-size: 12px; color: #475569; font-weight: 700;">একক মূল্য</th>
                                        <th align="right" style="padding: 8px 10px; font-size: 12px; color: #475569; font-weight: 700;">মোট</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {$itemsHtml}
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" align="right" style="padding: 8px 10px; font-size: 13px; color: #64748B;">সাবটোটাল:</td>
                                        <td align="right" style="padding: 8px 10px; font-size: 13px; font-weight: 600; color: #0F172A;">৳ {$subtotal}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="3" align="right" style="padding: 8px 10px; font-size: 13px; color: #64748B;">ডেলিভারি ফি:</td>
                                        <td align="right" style="padding: 8px 10px; font-size: 13px; font-weight: 600; color: #0F172A;">৳ {$deliveryCharge}</td>
                                    </tr>
                                    <tr style="background-color: #F0FDF4;">
                                        <td colspan="3" align="right" style="padding: 10px; font-size: 14px; font-weight: 800; color: #00684A;">সর্বমোট প্রদেয় বিল:</td>
                                        <td align="right" style="padding: 10px; font-size: 16px; font-weight: 800; color: #00684A;">৳ {$totalAmount}</td>
                                    </tr>
                                </tfoot>
                            </table>

                            <p style="margin: 0; font-size: 12px; color: #64748B; text-align: center;">
                                আমাদের শপে অর্ডার করার জন্য ধন্যবাদ। পার্সেল ডেলিভারিম্যান থেকে গ্রহণের সময় ইনভয়েস মিলিয়ে নিন।
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #F1F5F9; padding: 16px; text-align: center; border-top: 1px solid #E2E8F0;">
                            <p style="margin: 0; font-size: 12px; color: #64748B;">{$storeName} &bull; Powered by Ponnostack Cloud Commerce</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
HTML;

        // 1. Send to Customer if email provided
        if (!empty($customerEmail)) {
            self::send($customerEmail, $customerName, $subject, $html);
        }

        // 2. Also send alert notification to Store Owner
        $ownerEmail = $tenant['owner_email'] ?? '';
        if (!empty($ownerEmail) && $ownerEmail !== $customerEmail) {
            $ownerSubject = "নতুন অর্ডার প্রাপ্তি! — #{$orderNum} (৳ {$totalAmount})";
            self::send($ownerEmail, $tenant['owner_name'] ?? 'Store Admin', $ownerSubject, $html);
        }

        return true;
    }
}
