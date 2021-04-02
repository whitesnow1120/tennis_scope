import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import MatchItem from '../components/MatchItem';
import { getInplayData } from '../apis';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const Inplay = () => {
  const [inplayData, setInplayData] = useState([]);
  const [loading, setLoading] = useState(false);

  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  useEffect(() => {
    const loadInplayData = async () => {
      const response = await getInplayData();
      if (response.status === 200) {
        setInplayData(response.data);
      } else {
        setInplayData([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadInplayData();
      }, 1000 * 60 * 10);
    };

    loadInplayData();
  }, []);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Inplay</title>
        <meta property="og:title" content={SITE_SEO_TITLE} />
        <meta name="description" content={SITE_SEO_DESCRIPTION} />
        <meta property="og:description" content={SITE_SEO_DESCRIPTION} />
      </Helmet>
      {loading && (
        <div className="loading">
          <div className="loader">
            <BounceLoader loading={loading} css={override} size={100} />
          </div>
        </div>
      )}
      <section className="section inplay">
        <div className="container-fluid">
          <div className="row mt-4">
            {inplayData.length > 0 ? (
              inplayData.map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="inplay"
                  loading={loading}
                  setLoading={setLoading}
                />
              ))
            ) : (
              <></>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default Inplay;
