import React, { useEffect } from 'react';
import { useDispatch, useSelector } from 'react-redux';
import { Helmet } from 'react-helmet';

import MatchItem from '../components/MatchItem';
import { getInplayData } from '../apis';
import { GET_INPLAY_DATA } from '../store/actions/types';
import { SITE_SEO_TITLE, SITE_SEO_DESCRIPTION } from '../common/Constants';

const Inplay = () => {
  const dispatch = useDispatch();
  const { inplayData } = useSelector((state) => state.tennis);

  useEffect(() => {
    const loadInplayData = async () => {
      const response = await getInplayData();
      if (response.status === 200) {
        dispatch({
          type: GET_INPLAY_DATA,
          payload: response.data,
        });
      } else {
        dispatch({ type: GET_INPLAY_DATA, payload: [] });
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
      <section className="section inplay">
        <div className="container-fluid">
          <div className="row mt-4">
            {inplayData.length > 0 ? (
              inplayData.map((item) => (
                <MatchItem key={item.id} item={item} type="inplay" />
              ))
            ) : (
              <div className="no-result col-12">
                <span>There is no inplay data</span>
              </div>
            )}
          </div>
        </div>
      </section>
    </>
  );
};

export default Inplay;
