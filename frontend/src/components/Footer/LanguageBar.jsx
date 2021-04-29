import React from 'react';
import PropTypes from 'prop-types';
import { Link } from 'react-router-dom';

import logoFooterIcon from '../../assets/img/logo_footer.png';

const LanguageBar = (props) => {
  const { activeLanguage, handleLangugeClicked, type = '' } = props;

  return (
    <div className="text-sm-left">
      <div className={activeLanguage === 1 ? 'language active' : 'language'}>
        <span onClick={() => handleLangugeClicked(1)}>EU</span>
      </div>
      <div
        className={
          activeLanguage === 2 ? 'language active ml-2' : 'language ml-2'
        }
      >
        <span onClick={() => handleLangugeClicked(2)}>RU</span>
      </div>
      <div
        className={
          activeLanguage === 3 ? 'language active ml-2' : 'language ml-2'
        }
      >
        <span onClick={() => handleLangugeClicked(3)}>ES</span>
      </div>
      {type !== 'mobile' && (
        <div className="ml-4">
          <Link to="/" className="text-foot">
            <img src={logoFooterIcon} alt="footer logo" />
          </Link>
        </div>
      )}
    </div>
  );
};

LanguageBar.propTypes = {
  activeLanguage: PropTypes.number,
  handleLangugeClicked: PropTypes.func,
  type: PropTypes.string,
};

export default LanguageBar;
