import React from 'react';

const LoadingMatchList = () => {
  return (
    <>
      {new Array(12).fill(0).map((item, index) => (
        <div
          className="col-lg-4 col-md-6 col-sm-6 col-xs-12 mb-2 pb-2 pt-2 match-item"
          key={index}
          item={item}
        >
          <div className="match-box">
            <div className="loading-match">
              <div className="loading-matchlist-left">
                <div className="header-line"></div>
                <div className="footer-line"></div>
              </div>
              <div className="loading-matchlist-right">
                <div className="header-line"></div>
                <div className="footer-line"></div>
              </div>
              <div className="loading-matchlist-center">
                <div className="header-line"></div>
                <div className="footer-line"></div>
              </div>
            </div>
          </div>
        </div>
      ))}
    </>
  );
};

export default LoadingMatchList;
