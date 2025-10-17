<?php

function tpl_main_logo(){
    ?>
    <div class="b-header" style="    height: 60px;
    margin: 50px auto -39px;">
        <a href="/index.php" class="b-logo">
            <img src="/images/dragon_logo.png" alt="" width="100%" style="position: relative;top: 11px;left: -10px;">
        </a>
    </div>
    <?
}

function tpl_menu_main($page = 'main'){
    global $session_user;
	//require_once("lib/admin.lib");
	//$users_online_count = admin_user_get_online();
    ?>
    <a href="index.php" class="b-mainmenu__item-blue b-mainmenu__item-blue_first" >
        <span class="b-mainmenu__item-blue-inner" data-font="PTSans"><b  style="margin-top: 5px;">ГЛАВНАЯ</b></span>
    </a>
    <a href="rating.php" class="b-mainmenu__item-red b-mainmenu__item-red" >
        <span class="b-mainmenu__item-red-inner" data-font="PTSans">РЕЙТИНГ</span>
    </a>
    <a href="forum.php" class="b-mainmenu__item-red b-mainmenu__item-red" >
        <span class="b-mainmenu__item-red-inner" data-font="PTSans">ФОРУМ</span>
    </a>
   <!-- <a href="library.php" class="b-mainmenu__item-red b-mainmenu__item-red" >
        <span class="b-mainmenu__item-red-inner" data-font="PTSans">БИБЛИОТЕКА</span>
    </a> -->
    <?if($page == 'forum' && !$session_user){?>
    <a href="index.php?req_auth=1" onclick="return openPopup('#authPopup');" class="b-mainmenu__item-red b-mainmenu__item-red" >
        <span class="b-mainmenu__item-red-inner" data-font="PTSans">ВОЙТИ НА ФОРУМ</span>
    </a>   <!--<a href="comm.php" class="b-mainmenu__item-yellow b-mainmenu__item-yellow" >
        <span class="b-mainmenu__item-yellow-inner" data-font="PTSansBlack">УСЛУГИ</span>
    </a>-->
    <?}?>
    <a href="register.php" class="b-mainmenu__item-blue b-mainmenu__item-blue_last" >
        <span class="b-mainmenu__item-blue-inner" data-font="PTSans">РЕГИСТРАЦИЯ</span>
    </a>

    
    
	
	
	
    <?
}

function tpl_popup_form($page = 'main'){
    global $session_user;

    $text_0 = "Авторизация";
    $text_1 = "Вход в игру";
    $text_2 = "Начать игру";
    $act = "login.php";
    if($page == 'forum'){
        $text_0 = "Авторизация на форуме";
        $text_1 = "Вход на форум";
        $text_2 = "Войти";
        $act = "login.php?&req_on=forum.php&req_err=forum.php";
    }
    ?>
    <div id="authPopup" class="b-common-popup">
        <div class="b-common-block b-common-block__auth">
            <span class="b-common-block__l"></span>
            <span class="b-common-block__r"></span>
            <span class="b-common-block__t b-common-block__t_2"></span>
            <span class="b-common-block__b"></span>

            <span class="b-common-block__decor-tl"></span>
            <span class="b-common-block__decor-tr"></span>
            <span class="b-common-block__decor-bl"></span>
            <span class="b-common-block__decor-br"></span>

            <span class="b-common-block__decor-tl-2"></span>
            <span class="b-common-block__decor-tr-2"></span>
            <span class="b-common-block__decor-bl-2"></span>
            <span class="b-common-block__decor-br-2"></span>

            <span class="b-common-block__header">
				<span class="b-common-block__header-inner">
					<span data-font="PTSans" style="top: 6px; position: relative;"><?=$text_0;?></span>
				</span>
			</span>

            <span class="b-common-block__close" data-button="close"></span>

            <div class="b-common-block__cont">
                <div class="b-common-block__bgl">
                    <div class="b-common-block__bgr clearfix">

                        <form action="/<?=$act?>" name="enter" method="post" class="b-auth-form">
                            <table class="b-common-form__table">
                                <tr>
                                    <td class="valign-middle">
                                        <div class="b-news-footer b-news-footer_soc">
                                            <span class="b-news-footer__t"></span>
                                            <span class="b-news-footer__b"></span>

                                            <span class="b-news-footer__tl"></span>
                                            <span class="b-news-footer__tr"></span>
                                            <span class="b-news-footer__bl"></span>
                                            <span class="b-news-footer__br"></span>

                                            <div class="b-news-footer__cont clearfix" style="text-align:center;">
                                                <b><?=$text_1;?></b>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <table class="b-common-form__table">
                                <!--<tr>
                                    <td>
                                        <label class="b-common-form__label" for="form[server-name]">Сервер:</label>
                                    </td>
                                    <td>
                                        <select name="form[server-name]" id="server-name" onchange="window.location.href=this.value">
                                                                                            <option value="http://test.dwar.ru" selected="selected">ФЭО-Тест</option>
                                                                                            <option value="http://test2.dwar.ru/" >ФЭО-Тест2</option>
                                        </select>
                                    </td>
                                </tr>-->
                                <tr>
                                    <td>
                                        <label class="b-common-form__label" for="userEmail">E-mail:</label>
                                    </td>
                                    <td>
										<span class="b-common-form__field">
											<span class="b-common-form__field-inner">
												<input type="text" name="email" id="userEmail" value="" />
											</span>
										</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label class="b-common-form__label" for="userPassword">Пароль:</label>
                                    </td>
                                    <td>
										<span class="b-common-form__field">
											<span class="b-common-form__field-inner">
												<input type="password" name="passwd" id="userPassword" />
											</span>
										</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        <a href="send_password.php" class="invert" onclick="changePopup('#authPopup', '#recoveryPasswordStep1'); return false;"><b>Я забыл пароль</b></a>
                                    </td>
                                </tr>
                                <?if(defined('RESTORE_USER_ACTIVITY') && RESTORE_USER_ACTIVITY){?>
                                <tr>
                                    <td>&nbsp;</td>
                                    <td>
                                        <a href="restore_user.php" class="invert"><b>Восстановление доступа</b></a>
                                    </td>
                                </tr>
                                <?}?>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <button type="submit" class="b-button-red-5">
											<span class="b-button-red-5__inner">
												<?=$text_2;?>										</span>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </form>

                        <div class="b-divider-4"></div>

                        <p class="text-center">
                            <b>Впервые у нас? Скорее зарегистрируйтесь!</b>
                        </p>

                        <p class="text-center">
                            <a href="register.php" class="b-button-green-5">
								<span class="b-button-green-5__inner">
									Зарегистрироваться								</span>
                            </a>
                        </p>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="recoveryPasswordStep1" class="b-common-popup">
        <div class="b-common-block b-common-block__auth">
            <span class="b-common-block__l"></span>
            <span class="b-common-block__r"></span>
            <span class="b-common-block__t b-common-block__t_2"></span>
            <span class="b-common-block__b"></span>

            <span class="b-common-block__decor-tl"></span>
            <span class="b-common-block__decor-tr"></span>
            <span class="b-common-block__decor-bl"></span>
            <span class="b-common-block__decor-br"></span>

            <span class="b-common-block__decor-tl-2"></span>
            <span class="b-common-block__decor-tr-2"></span>
            <span class="b-common-block__decor-bl-2"></span>
            <span class="b-common-block__decor-br-2"></span>

            <span class="b-common-block__header">
				<span class="b-common-block__header-inner">
					<span data-font="PTSans">Напоминание пароля</span>
				</span>
			</span>

            <span class="b-common-block__close" data-button="close"></span>

            <div class="b-common-block__cont">
                <div class="b-common-block__bgl">
                    <div class="b-common-block__bgr clearfix" id="sendPasswordContainer">
                        <form action="<?=SERVER_URL?>send_password.php" method="post" class="b-auth-form" onsubmit="sendPasswordForm(event, this)">
                            <input type="hidden" name="ajax" value="1" />
                            <table class="b-common-form__table">
                                <tr>
                                    <td>
                                        <label class="b-common-form__label" for="form[email]">Ваш E-mail:</label>
                                    </td>
                                    <td>
										<span class="b-common-form__field">
											<span class="b-common-form__field-inner">
												<input type="text" name="form[email]" id="sendPasswordEmail" value="" />
											</span>
										</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="text-center">
                                        <button type="submit" class="b-button-red-5">
											<span class="b-button-red-5__inner">
												Продолжить											</span>
                                        </button>
                                    </td>
                                </tr>
                            </table>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="errorForm" class="b-common-popup" style="width:500px!important;">
        <div class="b-common-block b-common-block__auth">
            <span class="b-common-block__l"></span>
            <span class="b-common-block__r"></span>
            <span class="b-common-block__t b-common-block__t_2"></span>
            <span class="b-common-block__b"></span>

            <span class="b-common-block__decor-tl"></span>
            <span class="b-common-block__decor-tr"></span>
            <span class="b-common-block__decor-bl"></span>
            <span class="b-common-block__decor-br"></span>

            <span class="b-common-block__decor-tl-2"></span>
            <span class="b-common-block__decor-tr-2"></span>
            <span class="b-common-block__decor-bl-2"></span>
            <span class="b-common-block__decor-br-2"></span>

            <span class="b-common-block__header">
				<span class="b-common-block__header-inner">
					<span data-font="PTSans">Ошибка</span>
				</span>
			</span>

            <span class="b-common-block__close" data-button="close"></span>

            <div class="b-common-block__cont">
                <div class="b-common-block__bgl">
                    <div class="b-common-block__bgr clearfix" id="errorText" style="text-align:center;">
                        Ошибка
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        function sendPasswordForm(e, element) {
            $.Event(e).preventDefault();
            var $this = $(element);
            $.ajax({
                url: $this.attr('action'),
                data: $this.serialize(),
                type: 'POST',
                cache: false,
                success: function(response) {
                    $('#sendPasswordContainer').html(response);
                },
                error: function() {
                    alert('Произошла ошибка');
                }
            });
        }
    </script>
    <?
}