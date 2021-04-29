import styled from 'styled-components';

export const StyledBurger = styled.button`
  position: fixed;
  top: 17px;
  left: 17px;
  display: flex;
  flex-direction: column;
  justify-content: space-around;
  width: 2rem;
  height: 2rem;
  border: none;
  cursor: pointer;
  padding: 0;
  z-index: 9999;
  background: transparent;
  span {
    width: 32px;
    height: 2px;
    background: #7C859C;
    transition: all 0.3s linear;
    position: relative;
    transform-origin: 1px;
    :first-child {
      display: ${({ open }) => (open ? 'none' : '')};
    }
    :nth-child(2) {
      display: ${({ open }) => (open ? 'none' : '')};
    }
    :nth-child(3) {
      display: ${({ open }) => (open ? 'none' : '')};
    }
    :nth-child(4) {
      display: ${({ open }) => (open ? 'none' : '')};
    }
  }
`;
