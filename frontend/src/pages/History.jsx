import React, { useEffect, useState } from 'react';
import { useSelector } from 'react-redux';
import { Helmet } from 'react-helmet';
import { css } from '@emotion/core';
import BounceLoader from 'react-spinners/BounceLoader';
import PropTypes from 'prop-types';

import { filterByRankOdd } from '../utils';
import { getHistoryData } from '../apis';
import {
  SITE_SEO_TITLE,
  SITE_SEO_DESCRIPTION,
  SLIDER_RANGE,
  SLIDER_STEP,
} from '../common/Constants';
import CustomDatePicker from '../components/CustomDatePicker';
import MatchItem from '../components/MatchItem';
import RankButtonGroup from '../components/RankButtonGroup';
import CustomSlider from '../components/CustomSlider/slider';

const History = (props) => {
  const { filterChanged, setFilterChanged } = props;
  const { historyDate } = useSelector((state) => state.tennis);
  const rankFilter = localStorage.getItem('rankFilter');
  const [activeRank, setActiveRank] = useState(
    rankFilter === null ? '1' : rankFilter
  );
  const [historyData, setHistoryData] = useState([]);
  const [historyFilteredData, setHistoryFilteredData] = useState([]);
  const [loading, setLoading] = useState(false);

  const sliderChanged = JSON.parse(localStorage.getItem('sliderChanged'));
  const [sliderValue, setSliderValue] = useState(
    sliderChanged === null ? '0' : '1'
  );
  const defaultValues = sliderChanged === null ? SLIDER_RANGE : sliderChanged;
  const domain = SLIDER_RANGE;
  const [values, setValues] = useState(defaultValues.slice());
  const override = css`
    display: block;
    margin: 0 auto;
    border-color: red;
  `;

  const handleSliderChange = (value) => {
    setValues(value);
    setSliderValue(sliderValue === '0' ? '1' : '0');
    localStorage.setItem('sliderChanged', JSON.stringify(value));
  };

  const handleSliderUpdate = (value) => {
    setValues(value);
  };

  useEffect(() => {
    const loadHistoryData = async () => {
      const response = await getHistoryData(historyDate);
      if (response.status === 200) {
        setHistoryData(response.data);
        const filteredData = filterByRankOdd(response.data, activeRank, values);
        setHistoryFilteredData(filteredData);
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

  useEffect(() => {
    setFilterChanged(!filterChanged);
    const filteredData = filterByRankOdd(historyData, activeRank, values);
    setHistoryFilteredData(filteredData);
  }, [activeRank, sliderValue]);

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
              activeRank={activeRank}
              setActiveRank={setActiveRank}
            />
            <CustomSlider
              handleChange={handleSliderChange}
              handleUpdate={handleSliderUpdate}
              values={values}
              domain={domain}
              step={SLIDER_STEP}
            />
          </div>
          <div className="row mt-4">
            {historyFilteredData.length > 0 ? (
              historyFilteredData.map((item) => (
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

History.propTypes = {
  filterChanged: PropTypes.bool,
  setFilterChanged: PropTypes.func,
};

export default History;
