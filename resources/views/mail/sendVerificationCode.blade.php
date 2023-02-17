<!DOCTYPE html>

<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">

    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400&display=swap" rel="stylesheet">

    <!-- Styles -->
    <style>
        html,
        body {
            background-color: #fff;
            color: #636b6f;
            font-family: 'poppins', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }
    </style>

</head>

<body>

    <table role="presentation" border="0" bgcolor="#ffffff" cellpadding="0" cellspacing="0" style="margin:0 auto">

        <tbody>

            <tr>

                <td height="40px">&nbsp;</td>

            </tr>

            <tr>

                <td align="center" cellpadding="0">

                    <a href="#" target="_blank">

                        <img src="{{ env('APP_URL') }}/email/logo.png" style="margin:0 auto" width="128px">

                    </a>

                </td>

            </tr>

            <tr>

                <td align="center">

                    <span style="text-align:center;font-size:30px;font-weight:bold">

                        <p style="margin:40px 0 0">Olá</p>

                    </span>

                    <span style="text-align:center;font-size:18px;margin:5px 60px 30px;display:block">

                        Este é o código para acessar a sua conta:

                    </span>

                </td>

            </tr>

            <tr>

                <td>

                    <table width="100%" height="70px" border="0" cellpadding="0" cellspacing="0" style="min-width:340px">

                        <tbody>

                            <tr>

                                <td align="center" bgcolor="#18a4e0" style="border-radius:4px;text-align:center">

                                    <span style="text-align:center;font-size:36px;font-weight:bold;color:#fff;letter-spacing:20px">
                                    
                                        {{ $code }}

                                    </span>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td>

                    <table border="0" align="center" cellpadding="0" cellspacing="0">

                        <tbody>

                            <tr>

                                <td align="center">

                                    <span style="text-align:center;font-size:16px;margin:20px 0 40px;display:block;color:#a6a29f">

                                        Este código é solicitado para autenticação de sua conta, caso não tenha sido você ignore.

                                    </span>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td>

                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="10" style="margin:60px auto 0">

                        <tbody>

                            <tr>

                                <td height="1" bgcolor="#F5F0EB"></td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td align="center">

                    <div style="text-align:center;font-size:20px;margin:20px 0 0;font-weight:bold;color:#595756">Baixe nosso app</div>

                </td>

            </tr>

            <tr>

                <td>

                    <table height="80px" border="0" align="center" cellpadding="10" cellspacing="10">

                        <tbody>

                            <tr>

                                <td align="center">

                                    <a href="#" style="display:block" aria-label="Clique e faça Download do app na Play Store" target="_blank">

                                        <img src="{{ env('APP_URL') }}/email/play-store.png" style="margin:0 auto">

                                    </a>

                                </td>

                                <td align="center">

                                    <a href="#" style="display:block" aria-label="Clique e faça Download do app na App Store" target="_blank">

                                        <img src="{{ env('APP_URL') }}/email/apple-store.png" style="margin:0 auto">

                                    </a>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td>

                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="10">

                        <tbody>

                            <tr>

                                <td height="1" bgcolor="#F5F0EB"></td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td>

                    <table width="50%" height="70px" border="0" align="center" cellpadding="0" cellspacing="0">

                        <tbody>

                            <tr>

                                <td align="center">

                                    <a href="#" style="display:block" aria-label="Clique e conheça nossa página do Facebook" target="_blank">

                                        <img src="{{ env('APP_URL') }}/email/facebook-button.png" style="margin:0 auto">

                                    </a>

                                </td>

                                <td align="center">

                                    <a href="#" style="display:block" aria-label="Clique e conheça nosso Twitter" target="_blank">

                                        <img src="{{ env('APP_URL') }}/email/twitter-button.png" style="margin:0 auto">

                                    </a>

                                </td>

                                <td align="center">

                                    <a href="#" style="display:block" aria-label="Clique e conheça nosso canal do Youtube" target="_blank">

                                        <img src="{{ env('APP_URL') }}/email/youtube-button.png" style="margin:0 auto">

                                    </a>

                                </td>

                                <td align="center">

                                    <a href="#" style="display:block" aria-label="Clique e conheça nosso perfil do Instagram" target="_blank">

                                        <img src="{{ env('APP_URL') }}/email/instagram-button.png" style="margin:0 auto">

                                    </a>

                                </td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td>

                    <table border="0" align="center" cellpadding="0" cellspacing="10">

                        <tbody>

                            <tr>

                                <td height="1" bgcolor="#F5F0EB"></td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td>

                    <table width="100%" border="0" align="center" cellpadding="0" cellspacing="10">

                        <tbody>

                            <tr>

                                <td height="1" bgcolor="#F5F0EB"></td>

                            </tr>

                        </tbody>

                    </table>

                </td>

            </tr>

            <tr>

                <td>&nbsp;</td>

            </tr>

            <tr>

                <td>

                    <span style="text-align:center;font-size:14px;color:#a6a29f;line-height:18px">

                        <p style="margin:0"> © {{ date('Y') }} Meu Pedido - Todos os direitos reservados. </p>

                    </span>

                </td>

            </tr>

        </tbody>

    </table>

</body>

</html>