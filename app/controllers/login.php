<?php

/*
 * Bank
 * Copyright (C) 2015 Gunnar Beutner
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software Foundation
 * Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301, USA.
 */

require_once('helpers/csrf.php');
require_once('helpers/session.php');
require_once('helpers/otp.php');

class LoginController {
	public function get() {
		if (is_logged_in()) {
			header('Location: /app/transactions');
			die();
		}

		return [ 'login', null ];
	}

	public function post() {
		verify_csrf_token();

		$email = $_POST['email'];
		$password = $_POST['password'];
		$upassword = get_user_attr($email, 'password');
		$otp_token = $_POST['otp-token'];

		$uyubikey_identity = get_user_attr($email, 'yubikey_identity');
		if ($uyubikey_identity != '') {
			if ($otp_token == '') {
				$params = [
					'email' => $email,
					'password' => $password
				];
				return [ 'otp-login', $params ];
			}

			$yubikey_identity = get_yubikey_identity($otp_token);

			if ($yubikey_identity != $uyubikey_identity) {
				$params = [ 'message' => 'Das angegebene OTP-Token ist ungültig.' ];
				return [ 'error', $params ];
			}
		}

		if (!password_verify($password, $upassword)) {
			$params = [
				'message' => 'Benutzername oder Passwort falsch.'
			];
			return [ 'error', $params ];
		}

		set_user_session($email);
		header('Location: /app/transactions');
		die();
	}
}
