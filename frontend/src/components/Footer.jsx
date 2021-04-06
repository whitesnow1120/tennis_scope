import React, { useState } from 'react';
import { Link, Redirect } from 'react-router-dom';
import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';

import { CURRENT_YEAR } from '../common/Constants';
import logoFooterIcon from '../assets/img/logo_footer.png';
import telegramIcon from '../assets/img/telegram_icon.png';
import twitterIcon from '../assets/img/twitter_icon.png';

const Footer = (props) => {
  const { activeMenu, setActiveMenu } = props;
  const { userLoggedIn } = useSelector((state) => state.tennis);
  const [activeLanguage, setActiveLanguage] = useState(1);
  const loggedIn = localStorage.getItem('isLoggedIn');

  const handleMenuClicked = (menu) => {
    setActiveMenu(menu);
  };

  const handleLangugeClicked = (language) => {
    setActiveLanguage(language);
  };

  return (
    <>
      {loggedIn || userLoggedIn ? (
        <>
          <footer className="footer">
            <div className="container-fluid text-center">
              <div className="row align-items-center">
                <div className="col-lg-6 col-md-6 col-sm-12 col-xs-12 d-flex footer-language">
                  <div className="text-sm-left">
                    <div
                      className={
                        activeLanguage === 1 ? 'language active' : 'language'
                      }
                    >
                      <span onClick={() => handleLangugeClicked(1)}>EU</span>
                    </div>
                    <div
                      className={
                        activeLanguage === 2
                          ? 'language active ml-2'
                          : 'language ml-2'
                      }
                    >
                      <span onClick={() => handleLangugeClicked(2)}>RU</span>
                    </div>
                    <div
                      className={
                        activeLanguage === 3
                          ? 'language active ml-2'
                          : 'language ml-2'
                      }
                    >
                      <span onClick={() => handleLangugeClicked(3)}>ES</span>
                    </div>
                    <div className="ml-4">
                      <Link to="/" className="text-foot">
                        <img src={logoFooterIcon} alt="footer logo" />
                      </Link>
                    </div>
                  </div>
                </div>
                <div className="col-lg-6 col-md-12 col-sm-12 col-xs-12 subscribe">
                  <div className="text-sm-right foot-subscribe input-group">
                    <div className="footer-subscribe-label">
                      <label className="mb-0">
                        Subscribe to the weekly Digest!
                      </label>
                    </div>
                    <input
                      type="text"
                      className="form-control"
                      placeholder="Enter your email"
                    />
                    <div className="input-group-append">
                      <button className="btn" type="submit">
                        <span>Subscribe</span>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </footer>
          <footer className="footer footer-bar">
            <div className="container-fluid text-center">
              <div className="row align-items-center">
                <div className="col-lg-6 col-12">
                  <div className="text-sm-left footer-link">
                    <Link
                      to="/about-us"
                      className={
                        activeMenu === 7 ? 'text-foot active' : 'text-foot'
                      }
                      onClick={() => handleMenuClicked(7)}
                    >
                      About us
                    </Link>
                    <Link
                      to="/pricing"
                      className={
                        activeMenu === 8 ? 'text-foot active' : 'text-foot'
                      }
                      onClick={() => handleMenuClicked(8)}
                    >
                      Pricing
                    </Link>
                    <Link
                      to="/contact-us"
                      className={
                        activeMenu === 9 ? 'text-foot active' : 'text-foot'
                      }
                      onClick={() => handleMenuClicked(9)}
                    >
                      Contact us
                    </Link>
                    <Link
                      to="/terms-and-conditions"
                      className={
                        activeMenu === 10 ? 'text-foot active' : 'text-foot'
                      }
                      onClick={() => handleMenuClicked(10)}
                    >
                      Terms and Conditions
                    </Link>
                    <Link
                      to="/privacy-policy"
                      className={
                        activeMenu === 11 ? 'text-foot active' : 'text-foot'
                      }
                      onClick={() => handleMenuClicked(11)}
                    >
                      Privacy policy
                    </Link>
                  </div>
                </div>
                <div className="col-lg-6 col-12 footer-right">
                  <div className="text-sm-right">
                    <span className="mb-0">
                      © {CURRENT_YEAR} - {CURRENT_YEAR + 1}
                    </span>
                    <span className="mb-0 ml-2">Project Name</span>
                    <Link to="/twitter" className="text-foot ml-2">
                      <img src={twitterIcon} alt="twitter" />
                    </Link>
                    <Link to="/telegram" className="text-foot ml-2">
                      <img src={telegramIcon} alt="telegram" />
                    </Link>
                  </div>
                </div>
              </div>
            </div>
          </footer>
        </>
      ) : (
        <Redirect to="/" />
      )}
    </>
  );
};

Footer.propTypes = {
  activeMenu: PropTypes.number,
  setActiveMenu: PropTypes.func,
};

export default Footer;
