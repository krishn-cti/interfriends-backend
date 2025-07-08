<!DOCTYPE html PUBLIC "">
<html xmlns="">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Interfriends</title>
    <style type="text/css">
        body {
            margin: 0;
            padding: 0;
            min-width: 100% !important;
        }

        img {
            height: auto;
        }

        .content {
            width: 100%;
            max-width: 600px;
            background: #fff;
            border: 1px solid #ccc;
            margin: 20px auto;
        }

        .header {
            padding: 10px 30px 0px 30px;
        }

        .innerpadding {
            padding: 20px 30px 30px 30px !important;
        }

        .borderbottom {
            border-bottom: 1px solid #f2eeed;
        }

        .subhead {
            font-size: 15px;
            color: #ffffff;
            font-family: sans-serif;
            letter-spacing: 10px;
        }

        .h1,
        .h2,
        .bodycopy {
            color: #202020;
            font-family: sans-serif;
        }

        .h1 {
            font-size: 33px;
            line-height: 38px;
            font-weight: bold;
        }

        .h2 {
            padding: 0 0 15px 0;
            font-size: 24px;
            line-height: 28px;
            font-weight: bold;
            text-align: left;
        }

        .bodycopy {
            font-size: 16px;
        }

        .button {
            text-align: center;
            font-size: 18px;
            font-family: sans-serif;
            font-weight: bold;
            padding: 0 30px 0 30px;
        }

        .button a {
            color: #ffffff;
            text-decoration: none;
        }

        .footer {
            padding: 20px 30px 15px 30px;
        }

        .footercopy {
            font-family: sans-serif;
            font-size: 14px;
            color: #ffffff;
        }

        .footercopy a {
            color: #ffffff;
            text-decoration: underline;
        }

        p {
            text-align: left;
            line-height: 10px;
            font-size: 14px;
        }

        .ct_payment_bg img {
            width: 30px;
            object-fit: contain;
            filter: invert(1);
        }

        .ct_payment_bg {
            background-color: #63a540;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 25px;
            justify-content: center;
        }

        .ct_payment_bg h2 {
            margin-block: 0px;
            color: #fff;
            font-weight: 500;
        }

        .ct_amount_dtl {
            display: flex;
            gap: 40px;
            margin-bottom: 5px;
        }

        .ct_paid_amount_bg {
            display: grid;
            justify-content: center;
            background-color: #f6f6f6;
            padding: 40px;
        }

        .ct_footer_des {
            font-size: 16px;
            line-height: 20px;
            text-align: center;
        }

        .ct_mb_0 {
            margin-bottom: 0px;
        }

        .ct_mt_10 {
            margin-top: 10px;
            font-weight: 500;
        }

        @media only screen and (max-width: 550px),
        screen and (max-device-width: 550px) {
            body[yahoo] .hide {
                display: none !important;
            }

            body[yahoo] .buttonwrapper {
                background-color: transparent !important;
            }

            body[yahoo] .button {
                padding: 0px !important;
            }

            body[yahoo] .button a {
                background-color: #e05443;
                padding: 15px 15px 13px !important;
            }

            body[yahoo] .unsubscribe {
                display: block;
                margin-top: 20px;
                padding: 10px 50px;
                background: #2f3942;
                border-radius: 5px;
                text-decoration: none !important;
                font-weight: bold;
            }
        }
    </style>
</head>

<body>
    <table width="100%" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <td>
                <table class="content" align="center" cellpadding="0" cellspacing="0" border="0" style="border: 1px solid #ddd">
                    <tr>
                        <td bgcolor="#212E41 " class="header">
                            <table width="100%" align="left" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td height="70" style="padding: 20 20px 20px 0;">
                                        <!-- <img class="fix" src="https://interfriends.uk/interfriendsApp/assets/images/logo.png" width="200" height="auto" style="margin: 0 auto;display: block;" border="0" alt="" /> -->
                                        <img class="fix" src="https://creativethoughtsinfo.com/interfriendsApp/assets/img/interfriend_white.png" width="200" height="auto" style="margin: 0 auto;display: block;" border="0" alt="" />
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                    <tr>
                        <td class="innerpadding borderbottom">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0" style="max-width:600px;">

                                <tr>
                                    <td class="bodycopy" style="text-align:left;">

                                        <center>
                                            <div style="background-color: #63a540;padding: 15px;display: flex;align-items: center;gap: 25px;justify-content: center;">
                                                <img src="<?= base_url('assets/images/check-circle.png'); ?>" alt="check-circle.png" style=" width: 30px; height: 30px;object-fit: contain;filter: invert(1);">
                                                <h2 style=" margin-block: 0px;color: #fff;font-weight: 500;">Payment received</h2>
                                            </div>

                                            <h2 style="margin-bottom: 0px;"><strong>Hello, <span>Smith</span></strong></h2>
                                            <h4 style="margin-top: 10px;font-weight: 500;">We've got your payment. Thank You!</h4>
                                            <div style=" display: grid;justify-content: center;background-color: #f6f6f6;padding: 40px;">
                                                <div style="display: flex;gap: 40px;margin-bottom: 5px;">
                                                    <div><strong>Amount paid:</strong></div>
                                                    <div>£<?php echo $amount; ?></div>
                                                </div>
                                                <div style="display: flex;gap: 40px;margin-bottom: 5px;">
                                                    <div><strong>Payment date:</strong></div>
                                                    <div><?php echo $payment_date; ?></div>
                                                </div>
                                                <div style="display: flex;gap: 40px;margin-bottom: 5px;">
                                                    <div><strong>Payment status:</strong></div>
                                                    <div><?php echo $status; ?></div>
                                                </div>

                                            </div>
                                            <p style="font-size: 16px;line-height: 20px;text-align: center;margin-bottom: 0px;margin-top: 30px">If you don't currently pay by standing order, we recommend it - it really is the easiest way to pay. You can set it up online using our account info <br>Account name: Interfriends, sort Code:30-98-97, Acc No. 32774468.</p>
                                        </center>


                                    </td>
                                </tr>

                            </table>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>