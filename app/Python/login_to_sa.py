#!/usr/bin/python
import json

from selenium.common.exceptions import WebDriverException
from selenium.webdriver.common.by import By

from helpers import die, print_error, get_env, Fox


def main():
    @die("Couldn't fill form and log in on steamcommunity", Fox().stop, WebDriverException)
    def login():
        Fox().browser.find_element(By.NAME, 'username').send_keys(get_env('STEAM_USERNAME'))
        Fox().browser.find_element(By.NAME, 'password').send_keys(get_env('STEAM_PASSWORD'))
        Fox().browser.find_element(By.ID, 'imageLogin').click()

    Fox().browser.delete_all_cookies()
    Fox().browser.get('http://csgo.steamanalyst.com/?login')
    Fox.wait_for_redirect('steamcommunity.com', 2)
    login()
    Fox.wait_for_redirect('steamanalyst.com', 2)
    Fox.wait_for_element(By.ID, 'avatarL')
    user_agent = Fox().browser.execute_script("return navigator.userAgent;")

    print json.dumps({
        "cookies": Fox().browser.get_cookies(),
        "userAgent": user_agent
    })


if __name__ == '__main__':
    try:
        main()
    except (KeyboardInterrupt, SystemExit):
        Fox.stop()
    except WebDriverException as e:
        print_error(e.msg)
        Fox().stop()
