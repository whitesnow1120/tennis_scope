import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import MatchItem from '../components/MatchItem';
import { getTrigger1Data } from '../apis';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const Trigger1 = () => {
  const [trigger1Data, setTrigger1Data] = useState([]);
  const [loading, setLoading] = useState(false);

  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  useEffect(() => {
    const loadTrigger1Data = async () => {
      const response = await getTrigger1Data();
      if (response.status === 200) {
        setTrigger1Data(response.data);
      } else {
        setTrigger1Data([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadTrigger1Data();
      }, 1000 * 60 * 10);
    };

    loadTrigger1Data();
  }, []);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Trigger1</title>
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
      <section className="section trigger">
        <div className="container-fluid">
          <div className="row mt-4">
            {trigger1Data.length > 0 ? (
              trigger1Data.map((item) => (
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

export default Trigger1;
