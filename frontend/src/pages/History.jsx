import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import CustomDatePicker from '../components/CustomDatePicker';
import MatchItem from '../components/MatchItem';

import { getHistoryData } from '../apis';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const History = () => {
  const { historyDate } = useSelector((state) => state.tennis);
  const [historyData, setHistoryData] = useState([]);
  const [loading, setLoading] = useState(false);
  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  useEffect(() => {
    const loadHistoryData = async () => {
      const response = await getHistoryData(historyDate);
      if (response.status === 200) {
        setHistoryData(response.data);
      } else {
        setHistoryData([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadHistoryData();
      }, 1000 * 60 * 10);
    };

    loadHistoryData();
  }, [historyDate]);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : History</title>
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
      <section className="section history">
        <div className="container-fluid">
          <div className="datepicker-container">
            <CustomDatePicker />
          </div>
          <div className="row mt-4">
            {historyData.length > 0 ? (
              historyData.map((item) => (
                <MatchItem
                  key={item.id}
                  item={item}
                  type="history"
                  loading={loading}
                  setLoading={setLoading}
                />
              ))
            ) : (
              // <span className="no-result">There is no history data</span>
              <></>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default History;
