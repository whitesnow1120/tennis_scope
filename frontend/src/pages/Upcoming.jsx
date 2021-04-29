import React, { useState, useEffect } from 'react';
import { Helmet } from 'react-helmet';
import PropTypes from 'prop-types';

import { filterByRankOdd } from '../utils';
import { getUpcomingData } from '../apis';
import MatchItem from '../components/MatchItem';
import {
  SITE_SEO_TITLE,
  SITE_SEO_DESCRIPTION,
  SLIDER_RANGE,
  SLIDER_STEP,
} from '../common/Constants';
import RankButtonGroup from '../components/RankButtonGroup';
import CustomSlider from '../components/CustomSlider/slider';
import CustomCheckbox from '../components/CustomCheckbox';
import LoadingMatchList from '../components/LoadingMatchList';
import PerformanceStatistics from '../components/PerformanceStatistics';

const Upcoming = (props) => {
  const {
    filterChanged,
    setFilterChanged,
    roboPicks,
    setRoboPicks,
    performanceToday,
    mobileMatchClicked,
    setMobileMatchClicked,
  } = props;
  const [openedDetail, setOpenedDetail] = useState({
    p1_id: '',
    p2_id: '',
  });
  const [upcomingData, setUpcomingData] = useState([]);
  const [upcomingFilteredData, setUpcomingFilteredData] = useState([]);
  const [winners, setWinners] = useState([]);
  const [loading, setLoading] = useState(false);
  const [loadingMatchList, setLoadingMatchList] = useState(false);
  const rankFilter = localStorage.getItem('rankFilter');
  const [activeRank, setActiveRank] = useState(
    rankFilter === null ? '1' : rankFilter
  );

  const sliderChanged = JSON.parse(localStorage.getItem('sliderChanged'));
  const [sliderValue, setSliderValue] = useState(
    sliderChanged === null ? '0' : '1'
  );
  const defaultValues = sliderChanged === null ? SLIDER_RANGE : sliderChanged;
  const domain = SLIDER_RANGE;
  const [values, setValues] = useState(defaultValues.slice());

  const handleSliderChange = (value) => {
    setOpenedDetail({
      p1_id: '',
      p2_id: '',
    });
    setValues(value);
    setSliderValue(sliderValue === '0' ? '1' : '0');
    localStorage.setItem('sliderChanged', JSON.stringify(value));
  };

  const handleSliderUpdate = (value) => {
    setValues(value);
  };

  useEffect(() => {
    const loadUpcomingData = async () => {
      const response = await getUpcomingData();
      if (response.status === 200) {
        setWinners(response.data.winners);
        const data = response.data.upcoming_detail;
        const filteredData = filterByRankOdd(data, activeRank, values);
        setUpcomingData(data);
        setUpcomingFilteredData(filteredData);
      } else {
        setUpcomingData([]);
      }
      setLoadingMatchList(false);
      // Call the async function again
      setTimeout(function () {
        const pathName = window.location.pathname;
        if (pathName.includes('upcoming')) {
          loadUpcomingData();
        }
      }, 1000 * 60 * 5);
    };

    setLoadingMatchList(true);
    loadUpcomingData();
  }, []);

  useEffect(() => {
    setFilterChanged(!filterChanged);
    const filteredData = filterByRankOdd(upcomingData, activeRank, values);
    setUpcomingFilteredData(filteredData);
  }, [activeRank, sliderValue]);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Upcoming</title>
        <meta property="og:title" content={SITE_SEO_TITLE} />
        <meta name="description" content={SITE_SEO_DESCRIPTION} />
        <meta property="og:description" content={SITE_SEO_DESCRIPTION} />
      </Helmet>
      {loading && (
        <div className="loading">
          <div className="loader"></div>
        </div>
      )}
      <section
        className={`section upcoming ${
          mobileMatchClicked ? 'hide-filter' : ''
        } `}
      >
        <div className="container-fluid">
          <div className="row header-filter-group">
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
            <CustomCheckbox
              label="Robopicks"
              isChecked={roboPicks}
              setRoboPicks={setRoboPicks}
            />
            <PerformanceStatistics statistics={performanceToday} />
          </div>
          {!loadingMatchList ? (
            <div className="row matchlist-container">
              {upcomingFilteredData.length > 0 ? (
                upcomingFilteredData.map((item) => (
                  <MatchItem
                    key={item.event_id}
                    item={item}
                    type="upcoming"
                    loading={loading}
                    setLoading={setLoading}
                    openedDetail={openedDetail}
                    setOpenedDetail={setOpenedDetail}
                    winners={winners}
                    roboPicks={roboPicks}
                    mobileMatchClicked={mobileMatchClicked}
                    setMobileMatchClicked={setMobileMatchClicked}
                    matchCnt={upcomingFilteredData.length}
                  />
                ))
              ) : (
                <></>
              )}
            </div>
          ) : (
            <div className="row matchlist-container">
              <LoadingMatchList />
            </div>
          )}
        </div>
      </section>
    </>
  );
};

Upcoming.propTypes = {
  filterChanged: PropTypes.bool,
  setFilterChanged: PropTypes.func,
  roboPicks: PropTypes.bool,
  setRoboPicks: PropTypes.func,
  performanceToday: PropTypes.object,
  mobileMatchClicked: PropTypes.bool,
  setMobileMatchClicked: PropTypes.func,
};

export default Upcoming;
