import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';

import { filterByRankOdd } from '../utils';
import { getHistoryData } from '../apis';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';
import CustomDatePicker from '../components/CustomDatePicker';
import MatchItem from '../components/MatchItem';
import RankButtonGroup from '../components/RankButtonGroup';
import CustomSlider from '../components/CustomSlider/slider';

const History = () => {
  const { historyDate } = useSelector((state) => state.tennis);
  const [historyData, setHistoryData] = useState([]);
  const [loading, setLoading] = useState(false);
  const [activeFilter, setActiveFilter] = useState(1);
  const defaultValues = [1, 2];
  const domain = [1, 2];
  const [values, setValues] = useState(defaultValues.slice());
  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  const handleChange = (value) => {
    setValues(value);
  };

  useEffect(() => {
    const loadHistoryData = async () => {
      const response = await getHistoryData(historyDate);
      if (response.status === 200) {
        const filteredData = filterByRankOdd(
          response.data,
          activeFilter,
          values
        );
        setHistoryData(filteredData);
      } else {
        setHistoryData([]);
      }
      // Call the async function again
      setTimeout(function () {
        loadHistoryData();
      }, 1000 * 60 * 10);
    };

    loadHistoryData();
  }, [historyDate, activeFilter, values]);

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
          <div className="history-header">
            <div className="datepicker-container">
              <CustomDatePicker />
            </div>
            <RankButtonGroup
              setActiveFilter={setActiveFilter}
              activeFilter={activeFilter}
            />
            <CustomSlider
              handleChange={handleChange}
              values={values}
              domain={domain}
              step={0.1}
            />
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
              <></>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default History;
