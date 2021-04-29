import React, { useEffect, useState } from 'react';
import { Helmet } from 'react-helmet';
import PropTypes from 'prop-types';

import { filterByRankOdd, calculatePerformance } from '../utils';
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
import CustomCheckbox from '../components/CustomCheckbox';
import LoadingMatchList from '../components/LoadingMatchList';
import PerformanceStatistics from '../components/PerformanceStatistics';

const History = (props) => {
  const {
    filterChanged,
    setFilterChanged,
    roboPicks,
    setRoboPicks,
    mobileMatchClicked,
    setMobileMatchClicked,
  } = props;
  const [openedDetail, setOpenedDetail] = useState({
    p1_id: '',
    p2_id: '',
  });
  let historyDate = localStorage.getItem('historyDate');
  historyDate = historyDate !== null ? historyDate : new Date();
  const [date, setDate] = useState(historyDate);
  const rankFilter = localStorage.getItem('rankFilter');
  const [activeRank, setActiveRank] = useState(
    rankFilter === null ? '1' : rankFilter
  );
  const [loadingMatchList, setLoadingMatchList] = useState(false);
  const [historyData, setHistoryData] = useState([]);
  const [historyFilteredData, setHistoryFilteredData] = useState([]);
  const [winners, setWinners] = useState();
  const [loading, setLoading] = useState(false);
  const [performanceSpecificDay, setPerformanceSpecificDay] = useState();

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
    const loadHistoryData = async () => {
      let historyDate = localStorage.getItem('historyDate');
      historyDate = historyDate !== null ? historyDate : new Date();
      const response = await getHistoryData(historyDate);
      if (response.status === 200) {
        setWinners(response.data.winners);
        const data = response.data.history_detail;
        const filteredData = filterByRankOdd(data, activeRank, values);
        const performance = calculatePerformance(
          filteredData,
          response.data.winners
        );
        setPerformanceSpecificDay(performance);
        setHistoryData(data);
        setHistoryFilteredData(filteredData);
      } else {
        setHistoryData([]);
      }
      setLoadingMatchList(false);
      // Call the async function again
      setTimeout(function () {
        const pathName = window.location.pathname;
        let historyDate = localStorage.getItem('historyDate');
        historyDate = historyDate !== null ? historyDate : new Date();
        if (
          pathName.includes('history') &&
          historyDate.toString().slice(0, 15) ===
            new Date().toString().slice(0, 15)
        ) {
          loadHistoryData();
        }
      }, 1000 * 60 * 5);
    };

    setPerformanceSpecificDay(null);
    setLoadingMatchList(true);
    loadHistoryData();
  }, [date]);

  useEffect(() => {
    setFilterChanged(!filterChanged);
    const filteredData = filterByRankOdd(historyData, activeRank, values);
    setHistoryFilteredData(filteredData);
    if (winners !== undefined) {
      const performance = calculatePerformance(filteredData, winners);
      setPerformanceSpecificDay(performance);
    }
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
          <div className="loader"></div>
        </div>
      )}
      <section
        className={`section history ${
          mobileMatchClicked ? 'hide-filter' : ''
        } `}
      >
        <div className="container-fluid">
          <div className="row header-filter-group">
            <div className="datepicker-container">
              <CustomDatePicker setHistoryDate={setDate} historyDate={date} />
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
            <CustomCheckbox
              label="Robopicks"
              isChecked={roboPicks}
              setRoboPicks={setRoboPicks}
            />
            <PerformanceStatistics statistics={performanceSpecificDay} />
          </div>
          {!loadingMatchList ? (
            <div className="row matchlist-container">
              {historyFilteredData.length > 0 ? (
                historyFilteredData.map((item) => (
                  <MatchItem
                    key={item.event_id}
                    item={item}
                    type="history"
                    loading={loading}
                    setLoading={setLoading}
                    openedDetail={openedDetail}
                    setOpenedDetail={setOpenedDetail}
                    winners={winners}
                    roboPicks={roboPicks}
                    mobileMatchClicked={mobileMatchClicked}
                    setMobileMatchClicked={setMobileMatchClicked}
                    matchCnt={historyFilteredData.length}
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

History.propTypes = {
  filterChanged: PropTypes.bool,
  setFilterChanged: PropTypes.func,
  roboPicks: PropTypes.bool,
  setRoboPicks: PropTypes.func,
  mobileMatchClicked: PropTypes.bool,
  setMobileMatchClicked: PropTypes.func,
};

export default History;
