import React from 'react';

const Subscribe = () => {
  return (
    <div className="text-sm-right foot-subscribe input-group">
      <div className="footer-subscribe-label">
        <label className="mb-0">Subscribe to the weekly Digest!</label>
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
  );
};

export default Subscribe;
