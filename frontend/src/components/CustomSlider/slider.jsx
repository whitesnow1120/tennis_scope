import React from 'react';
import { Slider, Rail, Handles, Tracks, Ticks } from 'react-compound-slider';
import { SliderRail, Handle, Track, Tick } from './components';
import PropTypes from 'prop-types';

const CustomSlider = (props) => {
  const { handleChange, values, domain, step } = props;
  const reversed = false;
  const sliderStyle = {
    position: 'relative',
    width: '100%',
  };

  return (
    <div className="custom-slider">
      <Slider
        mode={3}
        step={step}
        domain={domain}
        reversed={reversed}
        rootStyle={sliderStyle}
        onChange={handleChange}
        values={values}
      >
        <Rail>
          {({ getRailProps }) => <SliderRail getRailProps={getRailProps} />}
        </Rail>
        <Handles>
          {({ handles, activeHandleID, getHandleProps }) => (
            <div className="slider-handles">
              {handles.map((handle) => (
                <Handle
                  key={handle.id}
                  handle={handle}
                  domain={domain}
                  isActive={handle.id === activeHandleID}
                  getHandleProps={getHandleProps}
                />
              ))}
            </div>
          )}
        </Handles>
        <Tracks left={false} right={false}>
          {({ tracks, getTrackProps }) => (
            <div className="slider-tracks">
              {tracks.map(({ id, source, target }) => (
                <Track
                  key={id}
                  source={source}
                  target={target}
                  getTrackProps={getTrackProps}
                />
              ))}
            </div>
          )}
        </Tracks>
        <Ticks count={10}>
          {({ ticks }) => (
            <div className="slider-ticks">
              {ticks.map((tick) => (
                <Tick key={tick.id} tick={tick} count={ticks.length} />
              ))}
            </div>
          )}
        </Ticks>
      </Slider>
    </div>
  );
};

CustomSlider.propTypes = {
  values: PropTypes.array,
  handleChange: PropTypes.func,
  domain: PropTypes.array,
  step: PropTypes.number,
};

export default CustomSlider;
