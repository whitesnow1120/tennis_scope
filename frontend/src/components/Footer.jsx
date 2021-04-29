import React, { useState } from 'react';
import { Redirect } from 'react-router-dom';
import { useSelector } from 'react-redux';
import PropTypes from 'prop-types';

import DesktopFooter from '../components/Footer/DesktopFooter';
import MobileFooter from '../components/Footer/MobileFooter';

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
          <div className="desktop">
            <DesktopFooter
              activeLanguage={activeLanguage}
              handleLangugeClicked={handleLangugeClicked}
              activeMenu={activeMenu}
              handleMenuClicked={handleMenuClicked}
            />
          </div>
          <div className="mobile">
            <MobileFooter
              activeMenu={activeMenu}
              setActiveMenu={setActiveMenu}
            />
          </div>
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
