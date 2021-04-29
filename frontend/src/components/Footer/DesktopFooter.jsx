import React from 'react';
import PropTypes from 'prop-types';

import LanguageBar from './LanguageBar';
import Subscribe from './Subscribe';
import FooterMenu from './FooterMenu';
import ExternalLink from './ExternalLink';

const DesktopFooter = (props) => {
  const {
    activeLanguage,
    handleLangugeClicked,
    activeMenu,
    handleMenuClicked,
  } = props;

  return (
    <>
      <footer className="footer">
        <div className="container-fluid text-center">
          <div className="row align-items-center">
            <div className="col-lg-6 col-md-6 col-sm-12 col-xs-12 d-flex footer-language">
              <LanguageBar
                activeLanguage={activeLanguage}
                handleLangugeClicked={handleLangugeClicked}
              />
            </div>
            <div className="col-lg-6 col-md-12 col-sm-12 col-xs-12 subscribe">
              <Subscribe />
            </div>
          </div>
        </div>
      </footer>
      <footer className="footer footer-bar">
        <div className="container-fluid text-center">
          <div className="row align-items-center">
            <div className="col-lg-6 col-12">
              <FooterMenu
                activeMenu={activeMenu}
                handleMenuClicked={handleMenuClicked}
              />
            </div>
            <div className="col-lg-6 col-12 footer-right">
              <ExternalLink />
            </div>
          </div>
        </div>
      </footer>
    </>
  );
};

DesktopFooter.propTypes = {
  activeLanguage: PropTypes.number,
  handleLangugeClicked: PropTypes.func,
  activeMenu: PropTypes.number,
  handleMenuClicked: PropTypes.func,
};

export default DesktopFooter;
