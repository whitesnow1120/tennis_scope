import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Helmet } from 'react-helmet';

import MatchItem from '../components/MatchItem';
import { getUpcomingData } from '../apis';
import { GET_UPCOMING_DATA } from '../store/actions/types';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const Upcoming = () => {
  const dispatch = useDispatch();
  const { upcomingData } = useSelector((state) => state.tennis);

  useEffect(() => {
    const loadUpcomingData = async () => {
      const response = await getUpcomingData();
      if (response.status === 200) {
        dispatch({
          type: GET_UPCOMING_DATA,
          payload: response.data,
        });
      } else {
        dispatch({ type: GET_UPCOMING_DATA, payload: [] });
      }
      // Call the async function again
      setTimeout(function () {
        loadUpcomingData();
      }, 1000 * 60 * 10);
    };

    loadUpcomingData();
  }, []);

  return (
    <>
      <Helmet>
        <title>{SITE_SEO_TITLE} : Upcoming</title>
        <meta property="og:title" content={SITE_SEO_TITLE} />
        <meta name="description" content={SITE_SEO_DESCRIPTION} />
        <meta property="og:description" content={SITE_SEO_DESCRIPTION} />
      </Helmet>
      <section className="section upcoming">
        <div className="container-fluid">
          <div className="row mt-4">
            {upcomingData.length > 0 ? (
              upcomingData.map((item) => (
                <MatchItem key={item.id} item={item} type="upcoming" />
              ))
            ) : (
              <div className="no-result col-12">
                <span>There is no upcoming data</span>
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default Upcoming;
