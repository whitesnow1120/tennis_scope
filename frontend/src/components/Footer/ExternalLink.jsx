import React from 'react';
import { Link } from 'react-router-dom';

import { CURRENT_YEAR } from '../../common/Constants';
import telegramIcon from '../../assets/img/telegram_icon.png';
import twitterIcon from '../../assets/img/twitter_icon.png';

const ExternalLink = () => {
  return (
    <div className="text-sm-right">
      <div className="footer-menu-project-name">
        <span className="mb-0">
          © {CURRENT_YEAR} - {CURRENT_YEAR + 1}
        </span>
        <span className="mb-0 ml-2">Project Name</span>
      </div>
      <div className="footer-menu-external-link">
        <Link to="/twitter" className="text-foot ml-2">
          <img src={twitterIcon} alt="twitter" />
        </Link>
        <Link to="/telegram" className="text-foot ml-2">
          <img src={telegramIcon} alt="telegram" />
        </Link>
      </div>
    </div>
  );
};

export default ExternalLink;
