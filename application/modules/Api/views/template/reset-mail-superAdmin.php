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
                                    <td height="70" style="padding: 10px;">
                                        <!-- <img class="fix" src="https://interfriends.uk/interfriendsApp/assets/images/logo.png" width="200" height="auto" style="margin: 0 auto;display: block;" border="0" alt="" /> -->
                                        <img class="fix" src="https://creativethoughtsinfo.com/interfriendsApp/assets/img/interfriend_white.png" width="200" height="auto" style="margin: 0 auto;display: block;" border="0" alt="" />
                                    </td>
                                </tr>
                            </table>

                        </td>
                    </tr>
                    <tr>
                        <td class="innerpadding borderbottom">
                            <table width="100%" border="0" cellspacing="0" cellpadding="0">

                                <tr>
                                    <td class="bodycopy" text-align:left;">

                                        <center>
                                            <div align="center"><img src="<?= base_url('assets/images/thankyou.png'); ?>" class="img-responsive"></div>
                                            <p></p>
                                            <h2 style="text-align: left;"><strong> You are one click away to reset your password </strong></h2>
                                            <p> <strong>Dear <?= $name; ?>,</strong> </p>
                                            <p style="color:#333"> Please <a href="<?= 'https://creativethoughtsinfo.com/interfriendsAdmin/#/resetPassword/' . $token; ?>">click here</a> to reset your password.</p>
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

    </td>
    </tr>
    </table>
</body>

</html>