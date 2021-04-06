import React from 'react';

import tennisPic from '../assets/img/tennis_pic.png';
import machineEffortPic from '../assets/img/machine_effort.png';
import humanIntuitiion from '../assets/img/human_intuition.png';
import Hero from '../components/Hero';

const AboutUs = () => {
  return (
    <>
      <Hero title="About us" />
      <div className="container pb-5 description">
        <div className="row">
          <div className="col-12 text-center sub-title">
            <h1>
              Data driven decision making for predicting tennis match outcomes
            </h1>
          </div>
        </div>
        <div className="row">
          <div className="col-lg-6 col-md-6 col-sm-12 col-xs-12 mt-4 padding-bottom">
            <p>
              In the beginning our mission was simple - create the best product
              in it’s class to easify determining tennis match outcomes with
              human intelligence assistance. The best means in terms of design
              and accessibility of the important data. We put focus on
              individual tennis player performance against other players.
            </p>
          </div>
          <div className="col-lg-6 col-md-6 col-sm-12 col-xs-12 mt-4 padding-bottom">
            <p>
              Tennis betting can be challenging, players ATP or WTA rank does
              not provide clear winners, tennis ELO rank system is a little bit
              better, but it’s still far away from perfect, because single
              number can not show full picture about player preparedness for the
              battle. We include detailed and important information about
              players, they willingness to battle and win.
            </p>
          </div>
        </div>
        <div className="row mt-5">
          <div className="col-12 text-center sub-title">
            <h1>Everyone is guessing</h1>
          </div>
        </div>
        <div className="row justify-content-center">
          <div className="col-12 guess justify-content-center mt-4 padding-bottom">
            <p>
              Our mission is to block out the noise and provide data-driven
              insights for tennis betting and trading to stay ahead of the curve
              be that bookmaker or trader. By using our product you will always
              have advantage and will be able to generate profits in the short
              and long run.
            </p>
          </div>
          <div className="col-12 text-center mt-4 tennis-pic">
            <img src={tennisPic} alt="tennis pic" />
          </div>
        </div>
        <div className="row mt-5">
          <div className="col-12 text-center sub-title">
            <h1>We bring machine efforts and human intuition together</h1>
          </div>
        </div>
        <div className="row mb-4 machine-human">
          <div className="col-lg-5 col-md-5 col-sm-12 col-xs-12 mt-4 padding-bottom machine-effort">
            <div className="">
              <div className="text-center mt-4">
                <img src={machineEffortPic} alt="machine effect" />
              </div>
              <div className="text-center sub-title mt-3">
                <h1>Machine effort</h1>
              </div>
              <div className="machine-text">
                <p>
                  We are harvesting massive amounts of information from official
                  sources, pulling it all into our growing stream of datafeeds.
                  Machines are doing their calculations that for humans would
                  take significant effort to process and apply.
                </p>
              </div>
            </div>
          </div>
          <div className="col-lg-5 col-md-5 col-sm-12 col-xs-12 mt-4 padding-bottom human-intuition">
            <div className="">
              <div className="text-center mt-4">
                <img src={humanIntuitiion} alt="human intuituion" />
              </div>
              <div className="text-center sub-title mt-3">
                <h1>Human Intuition</h1>
              </div>
              <div className="human-text">
                <p>
                  Our team and community are constantly testing the data’s
                  integrity and viability, and building simple interfaces to
                  turn this information into digestible, actionable insights.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </>
  );
};

export default AboutUs;
