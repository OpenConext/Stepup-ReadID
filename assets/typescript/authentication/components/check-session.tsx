import React, { useState, useEffect } from "react";
import VerifyAPI from "../app/api/verify-api";

export type Props = {
  verifyUrl: string;
  authenticationUrl: string;
  retryInSeconds: number;
  maxTries: number;
  timeOutMessage: string;
};

const CheckSession: React.FC<Props> = ({ verifyUrl, authenticationUrl, retryInSeconds, maxTries, timeOutMessage }) => {
  const [tries, setTries] = useState<number>(0);
  const [message, setMessage] = useState<string|undefined>(undefined);
  const [done, setDone] = useState<boolean>(false);
  const [wait, setWait] = useState<number>(0)

  const api = new VerifyAPI(verifyUrl);


  const sleep = (ms: number): Promise<void> => {
    let timeout:number;
    return new Promise(resolve => {
      timeout = setTimeout(resolve, ms);
    }).then(()=>{
      clearTimeout(timeout);
    });
  }

  useEffect(() => {

    const fetchStatus = async (): Promise<boolean> => {
      const response = await api.fetchStatus();

      if (response.success) {
        if (response.payload.confirmed) {
          return true;
        }
      } else {
        setMessage(response.error?.message);
      }
      return false;
    }

    const checkStatus = async (count: number): Promise<void> => {
      setTries(count)

      // Wait before check
      for (let i = 0; i < retryInSeconds; i++) {
        setWait(i);
        await sleep(1000)
      }
      setWait(retryInSeconds);

      // check state
      const result = await fetchStatus()
      if (result) {
        window.location.assign(authenticationUrl);
        return;
      }

      // update state
      count = count+1;

      // end polling when max duration exceeds
      if (count >= maxTries) {
        setDone(true);
        setMessage(timeOutMessage);
        setTries(maxTries)
        return;
      }

      await checkStatus(count)
    }

    (async () => {
      await checkStatus(0);
    })()
  }, []);

  const percentage = (current:number, max: number):number => {
    return 100 / max * current;
  }

  return (
      <div className="m-2 p-1">
        {!done && (
          <div>
            <div className="progress m-1" style={{ height: 1 + 'px' }}>
              <div className="progress-bar" role="progressbar" style={{ width: percentage(wait, retryInSeconds) + '%'}} aria-valuenow={ percentage(wait, retryInSeconds) } aria-valuemin={0} aria-valuemax={100}/>
            </div>
            <div className="progress m-1">
              <div className="progress-bar" role="progressbar" style={{ width: percentage(tries, maxTries) + '%'}} aria-valuenow={ percentage(tries, maxTries) } aria-valuemin={0} aria-valuemax={100}/>
            </div>
            <span className="d-none" data-testid="check-session-polling">{ wait + "/" + retryInSeconds + "," + tries + "/" + maxTries }</span>
          </div>
        )}
        {message && (
            <div className="alert alert-warning m-1" role="alert">{message}</div>
        )}
      </div>
  );
}

export default CheckSession;