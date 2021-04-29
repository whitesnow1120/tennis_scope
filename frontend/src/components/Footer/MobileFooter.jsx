import React, { useState } from 'react';
import PropTypes from 'prop-types';

import Subscribe from './Subscribe';
import ExternalLink from './ExternalLink';
import LanguageBar from './LanguageBar';
import FooterMenu from './FooterMenu';

const MobileFooter = (props) => {
  const { activeMenu, setActiveMenu, setOpen = null } = props;
  const [activeLanguage, setActiveLanguage] = useState(1);

  const handleMenuClicked = (menu) => {
    setActiveMenu(menu);
    if (setOpen) {
      setOpen(false);
    }
  };

  const handleLangugeClicked = (language) => {
    setActiveLanguage(language);
  };

  return (
    <div className="footer">
      <div className="mobile-footer-menu">
        <FooterMenu
          activeMenu={activeMenu}
          handleMenuClicked={handleMenuClicked}
        />
      </div>
      <div className="mobile-footer-subscribe subscribe">
        <Subscribe />
      </div>
      <div className="mobile-footer-language footer-language">
        <LanguageBar
          type="mobile"
          activeLanguage={activeLanguage}
          handleLangugeClicked={handleLangugeClicked}
        />
      </div>
      <div className="mobile-footer-project-name">
        <ExternalLink />
      </div>
    </div>
  );
};

MobileFooter.propTypes = {
  activeMenu: PropTypes.number,
  setActiveMenu: PropTypes.func,
  setOpen: PropTypes.any,
};

export default MobileFooter;
