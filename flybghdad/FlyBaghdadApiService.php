<?php

namespace App\ApiServices;

use Illuminate\Support\Facades\Http;

class FlyBaghdadService
{
    // add this into app.php file in config folder then add variable in .env file
    #env("ISO_COUNTRY", "IQ")
    #env("AIRLINE_VENDOR_ID", "IF")
    #env("ISO_CURRENCY", "USD")
    #env("AGENCY_ID", "ALMARYAOTA")
    #env("AGENCY_PASSWORD", "D+EDKnSPyI9PVgdCk+fMzv3qIeBEXM8yfAunvnWp1KA")
    #env("TARGET_API", "Test")
    #env("TARGET_URL", "https://flybaghdad.avtra.com/wsbe/rest/")
    #env("SEARCH_TYPE", "STANDARD")
    #env("SEQUENCE_NUMBER", "1")


    //"ISOCountry" => env("ISO_COUNTRY", "IQ"),
    //"AirlineVendorID" => env("AIRLINE_VENDOR_ID", "IF"),
    //"ISOCurrency" => env("ISO_CURRENCY", "USD"),
    //"AgencyID" => env("AGENCY_ID", "ALMARYAOTA"),
    //"AgencyPassword" => env("AGENCY_PASSWORD", "D+EDKnSPyI9PVgdCk+fMzv3qIeBEXM8yfAunvnWp1KA"),
    //"Target" => env("TARGET_API", "Test"),
    //"TargetURL" => env("TARGET_URL", "https://flybaghdad.avtra.com/wsbe/rest/"),
    //"SearchType" => env("SEARCH_TYPE", "STANDARD"),
    //"SequenceNmbr" => env("SEQUENCE_NUMBER", "10"),

    public function checkServiceAvailability(): string
    {
        $response = Http::withHeaders([
            'Accept' => 'application/xml',
            'Content-Type' => 'application/xml',
            'Authorization' => config('app.AgencyPassword'),
        ])->send('POST', config('app.TargetURL') . 'services/ping',
            [
                'body' => $this->getCheckServiceAvailability()
            ]);

        $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

        return json_encode($xml);
    }

    public function getCheckServiceAvailability(): string
    {
        return '<OTA_PingRQ xmlns="http://www.opentravel.org/OTA/2003/05"
                xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
                xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05 OTA_ReadRQ.xsd"
                EchoToken="50987" TimeStamp="2017-03-14T10:44:10+05:30"
                Target="' . config("app.Target") . '"
                Version="2.001" SequenceNmbr="' . config('app.SequenceNmbr') . '"
                PrimaryLangID="En-us">
                    <POS>
                        <Source
                            AirlineVendorID="' . config("app.AirlineVendorID") . '"
                            ISOCountry="' . config("app.ISOCountry") . '"
                            ISOCurrency="' . config("app.ISOCurrency") . '">
                            <RequestorID Type="5" ID="' . config("app.AgencyID") . '"/>
                        </Source>
                    </POS>
                    <EchoData>Echo me Back</EchoData>
                </OTA_PingRQ>';
    }

    public function getLowFareSearchOneWay($from, $to, $flightDate, $adults, $children, $infants, $flightReturnDate = null): string
    {
        $response = Http::withHeaders([
            'Accept' => 'application/xml',
            'Content-Type' => 'application/xml',
            'Authorization' => config('app.AgencyPassword')
        ])->send('post', config('app.TargetURL') . 'availability/lowfaresearch',
            [
                'body' => $this->getSchemaLowFareSearchOneWay($from, $to, $flightDate, $adults, $children, $infants, $flightReturnDate)
            ]);

        $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

        return json_encode($xml);
    }

    public function getSchemaLowFareSearchOneWay($from, $to, $flightDate, $adults, $children, $infants, $flightReturnDate): string
    {
        $xmlPart1 = '<OTA_AirLowFareSearchRQ xmlns="http://www.opentravel.org/OTA/2003/05"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05
            OTA_AirLowFareSearchRQ.xsd" EchoToken="50987" TimeStamp="2022-03-23T05:44:10+05:30"
            Target="' . config("app.Target") . '" Version="2.001" SequenceNmbr="' . config('app.SequenceNmbr') . '" PrimaryLangID="En-us">
            <POS>
                <Source
                AirlineVendorID="' . config("app.AirlineVendorID") . '"
                ISOCountry="' . config("app.ISOCountry") . '"
                ISOCurrency="' . config("app.ISOCurrency") . '">
                    <RequestorID Type="5" ID="' . config("app.AgencyID") . '"/>
                </Source>
            </POS>
            <OriginDestinationInformation>
                <DepartureDateTime>' . $flightDate . '</DepartureDateTime>
                <OriginLocation LocationCode="' . $from . '"/>
                <DestinationLocation LocationCode="' . $to . '"/>
            </OriginDestinationInformation>';

        $xmlPart3 = '<TravelPreferences >
                <CabinPref  Cabin=""/>
            </TravelPreferences>
            <TravelerInfoSummary>
                <AirTravelerAvail>
                    <PassengerTypeQuantity Code="ADT" Quantity="' . $adults . '"/>
                    <PassengerTypeQuantity Code="CHD" Quantity="' . $children . '"/>
                    <PassengerTypeQuantity Code="INF" Quantity="' . $infants . '"/>
                </AirTravelerAvail>
            </TravelerInfoSummary>
            <ProcessingInfo SearchType="' . config('app.SearchType') . '"/>
            </OTA_AirLowFareSearchRQ>';

        $mxlPart2 = '';

        if ($flightReturnDate != null) {
            $mxlPart2 = '<OriginDestinationInformation>
                    <DepartureDateTime>' . $flightReturnDate . '</DepartureDateTime>
                    <OriginLocation LocationCode="' . $to . '"/>
                    <DestinationLocation LocationCode="' . $from . '"/>
                </OriginDestinationInformation>';
        }


        return $xmlPart1 . $mxlPart2 . $xmlPart3;

    }

    public function getSeatAvailability(
        $from, $to, $departureDateTimeOneWay, $arrivalDateTimeOneWay, $rphOneWay, $airEquipTypeOneWay, $flightNumberOnneWay,
        $departureDateTimeTwoWay = null, $arrivalDateTimeTwoWay = null, $rphTwoWay = null, $airEquipTypeTwoWay = null,
        $flightNumberTwoWay = null, $duration, $adults, $children, $infants, $directionInd = "OneWay")
    {
        $response = Http::withHeaders([
            'Accept' => 'application/xml',
            'Content-Type' => 'application/xml',
            'Authorization' => config('app.AgencyPassword')
        ])->send('post', config('app.TargetURL') . 'ancillary/seatmapdetailsv2',
            [
                'body' => $this->getSchemaSeatAvailability($from, $to, $departureDateTimeOneWay, $arrivalDateTimeOneWay, $rphOneWay, $airEquipTypeOneWay, $flightNumberOnneWay,
                    $departureDateTimeTwoWay, $arrivalDateTimeTwoWay, $rphTwoWay, $airEquipTypeTwoWay, $flightNumberTwoWay, $duration, $adults, $children, $infants, $directionInd)
            ]);

        $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

        return json_encode($xml);
    }

    public function getSchemaSeatAvailability(
        $from, $to, $departureDateTimeOneWay, $arrivalDateTimeOneWay, $rphOneWay, $airEquipTypeOneWay,
        $flightNumberOneWay,
        $departureDateTimeTwoWay = null, $arrivalDateTimeTwoWay = null, $rphTwoWay = null, $airEquipTypeTwoWay = null,
        $flightNumberTwoWay = null,
        $duration, $adults, $children, $infants, $directionInd = "OneWay")
    {

        $xmlPart1 = '<OTA_AirSeatMapV2RQ xmlns="http://www.opentravel.org/OTA/2003/05"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05
            OTA_AirLowFareSearchRQ.xsd" EchoToken="50987" TimeStamp="2022-03-23T05:44:10+05:30"
            Target="' . config("app.Target") . '"
            Version="2.001" SequenceNmbr="' . config('app.SequenceNmbr') . '" PrimaryLangID="En-us">
            <POS>
                <Source
                AirlineVendorID="' . config("app.AirlineVendorID") . '"
                ISOCountry="' . config("app.ISOCountry") . '"
                ISOCurrency="' . config("app.ISOCurrency") . '">
                    <RequestorID Type="5" ID="' . config("app.AgencyID") . '" AgentCode="" IATACode="" AccountNumber=""/>
                </Source>
            </POS>';

        $xmlPart2 = '<SeatMapRequests>
		<SeatMapRequest TravelerRefNumberRPHs="">
			<AirItinerary DirectionInd="' . $directionInd . '">
				<OriginDestinationOptions>
					<OriginDestinationOption RefNumber="1">
			             <FlightSegment FlightNumber="' . $flightNumberOneWay . '" DepartureDateTime="' . $departureDateTimeOneWay . '" ArrivalDateTime="' . $arrivalDateTimeOneWay . '" Duration="' . $duration . '" StopQuantity="0" RPH="' . $rphOneWay . '">
			                <DepartureAirport LocationCode="' . $from . '" Terminal="T1"/>
			                <ArrivalAirport LocationCode="' . $to . '" Terminal="T2"/>
			                <OperatingAirline Code="' . config("app.AirlineVendorID") . '"/>
			                <Equipment AirEquipType="' . $airEquipTypeOneWay . '"/>
			             </FlightSegment>
                    	</OriginDestinationOption>';

        $xmlPart3 = '';

        if ($directionInd == "Return") {
            $xmlPart3 = '<OriginDestinationOption RefNumber="2">
			             <FlightSegment FlightNumber="' . $flightNumberTwoWay . '" DepartureDateTime="' . $departureDateTimeTwoWay . '" ArrivalDateTime="' . $arrivalDateTimeTwoWay . '" Duration="' . $duration . '" StopQuantity="0" RPH="' . $rphTwoWay . '">
			                <DepartureAirport LocationCode="' . $to . '" Terminal="T1"/>
			                <ArrivalAirport LocationCode="' . $from . '" Terminal="T2"/>
			                <OperatingAirline Code="' . config("app.AirlineVendorID") . '"/>
			                <Equipment AirEquipType="' . $airEquipTypeTwoWay . '"/>
			             </FlightSegment>
                    	</OriginDestinationOption>';
        }

        $xmlPart4 = '</OriginDestinationOptions>
                        </AirItinerary>
                        <TravelerInfoSummary>
                            <AirTravelerAvail>
                                <PassengerTypeQuantity Code="ADT" Quantity="' . $adults . '"/>
                                <PassengerTypeQuantity Code="CHD" Quantity="' . $children . '"/>
                                <PassengerTypeQuantity Code="INF" Quantity="' . $infants . '"/>
                            </AirTravelerAvail>
                        </TravelerInfoSummary>
                    </SeatMapRequest>
                </SeatMapRequests>
            </OTA_AirSeatMapV2RQ>';
        return $xmlPart1 . $xmlPart2 . $xmlPart3 . $xmlPart4;
    }


    public function createBooking($from, $to, $departureDateTimeOneWay, $arrivalDateTimeOneWay, $rphOneWay, $airEquipTypeOneWay,
                                  $flightNumberOneWay, $departureDateTimeTwoWay = null, $arrivalDateTimeTwoWay = null, $rphTwoWay = null,
                                  $airEquipTypeTwoWay = null, $flightNumberTwoWay = null, $duration, $adults, $children, $infants, $directionInd = "OneWay", $bookingRefCodeOneWay = "A12",
                                  $bookingRefCodeTwoWay = "A12", $bookingRefCodeDesginOneWay = "9", $bookingRefCodeDesginTwoWay = "9",
                                  $baseFareOneWay = "0", $baseFareTwoWay = "0", $taxesOneWay = "0", $taxesTwoWay = "0", $totalFareOneWay = "0",
                                  $totalFareTwoWay = "0", $currencyCode = "USD", $adultsInfo = null, $childrenInfo = null, $infantsInfo = null, $status = "1", $instance = "0", $PNR = "5TLNA7", $initialBooking = true)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/xml',
            'Content-Type' => 'application/xml',
            'Authorization' => config('app.AgencyPassword')
        ])->send('post', config('app.TargetURL') . 'booking/create',
            [
                'body' => $this->getSchemaCreateBooking($from, $to, $departureDateTimeOneWay, $arrivalDateTimeOneWay, $rphOneWay, $airEquipTypeOneWay,
                    $flightNumberOneWay, $departureDateTimeTwoWay, $arrivalDateTimeTwoWay, $rphTwoWay,
                    $airEquipTypeTwoWay, $flightNumberTwoWay, $duration, $adults, $children, $infants, $directionInd, $bookingRefCodeOneWay,
                    $bookingRefCodeTwoWay, $bookingRefCodeDesginOneWay, $bookingRefCodeDesginTwoWay,
                    $baseFareOneWay, $baseFareTwoWay, $taxesOneWay, $taxesTwoWay, $totalFareOneWay,
                    $totalFareTwoWay, $currencyCode, $adultsInfo, $childrenInfo, $infantsInfo, $status = "1", $instance = "0", $PNR = "5TLNA7", true),
            ]);

        $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

        return json_encode($xml);
    }

    public function getSchemaCreateBooking($from, $to, $departureDateTimeOneWay, $arrivalDateTimeOneWay, $rphOneWay, $airEquipTypeOneWay,
                                           $flightNumberOneWay, $departureDateTimeTwoWay = null, $arrivalDateTimeTwoWay = null, $rphTwoWay = null,
                                           $airEquipTypeTwoWay = null, $flightNumberTwoWay = null, $duration, $adults, $children, $infants, $directionInd = "OneWay", $bookingRefCodeOneWay = "A12",
                                           $bookingRefCodeTwoWay = "A12", $bookingRefCodeDesginOneWay = "9", $bookingRefCodeDesginTwoWay = "9",
                                           $baseFareOneWay = "0", $baseFareTwoWay = "0", $taxesOneWay = "0", $taxesTwoWay = "0", $totalFareOneWay = "0",
                                           $totalFareTwoWay = "0", $currencyCode = "USD", $adultsInfo = null, $childrenInfo = null, $infantsInfo = null, $status = "1", $instance = "0", $PNR = "5TLNA7", $initialBooking = true)
    {
        $xmlPart1 = '<OTA_AirBookRQ xmlns="http://www.opentravel.org/OTA/2003/05"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05
            OTA_AirBookRQ.xsd" EchoToken="50987" TimeStamp="2022-03-23T05:44:10+05:30"
            Target="' . config("app.Target") . '"
            Version="2.001" SequenceNmbr="' . config('app.SequenceNmbr') . '" PrimaryLangID="En-us">
            <POS>
                <Source
                AirlineVendorID="' . config("app.AirlineVendorID") . '"
                ISOCountry="' . config("app.ISOCountry") . '"
                ISOCurrency="' . config("app.ISOCurrency") . '">
                    <RequestorID Type="5" ID="' . config("app.AgencyID") . '" AgentCode="" IATACode="" AccountNumber=""/>
                </Source>
            </POS>';

        $xmlPart2 = '<AirItinerary DirectionInd="' . $directionInd . '">
                        <OriginDestinationOptions>
                            <OriginDestinationOption>
                                <FlightSegment
                                FlightNumber="' . $flightNumberOneWay . '"
                                DepartureDateTime="' . $departureDateTimeOneWay . '"
                                ArrivalDateTime="' . $arrivalDateTimeOneWay . '"
                                Duration="' . $duration . '" StopQuantity="0" RPH="' . $rphOneWay . '"
                                ResBookDesigCode="' . $bookingRefCodeOneWay . '">
                                    <DepartureAirport LocationCode="' . $from . '"/>
                                    <ArrivalAirport LocationCode="' . $to . '"/>
                                    <OperatingAirline Code="' . config("app.AirlineVendorID") . '"/>
                                    <Equipment AirEquipType="' . $airEquipTypeOneWay . '"/>
                                    <BookingClassAvails>
                                        <BookingClassAvail ResBookDesigCode="' . $bookingRefCodeOneWay . '"
                                        ResBookDesigQuantity="' . $bookingRefCodeDesginOneWay . '"/>
                                    </BookingClassAvails>
                                </FlightSegment>
                            </OriginDestinationOption>';

        $xmlPart3 = '';
        if ($directionInd == 'Return') {
            $xmlPart3 = '<OriginDestinationOption>
                                <FlightSegment
                                FlightNumber="' . $flightNumberTwoWay . '"
                                DepartureDateTime="' . $departureDateTimeTwoWay . '"
                                ArrivalDateTime="' . $arrivalDateTimeTwoWay . '"
                                Duration="' . $duration . '" StopQuantity="0" RPH="' . $rphTwoWay . '"
                                ResBookDesigCode="' . $bookingRefCodeTwoWay . '">
                                    <DepartureAirport LocationCode="' . $to . '"/>
                                    <ArrivalAirport LocationCode="' . $from . '"/>
                                    <OperatingAirline Code="' . config("app.AirlineVendorID") . '"/>
                                    <Equipment AirEquipType="' . $airEquipTypeTwoWay . '"/>
                                    <BookingClassAvails>
                                        <BookingClassAvail ResBookDesigCode="' . $bookingRefCodeTwoWay . '"
                                        ResBookDesigQuantity="' . $bookingRefCodeDesginTwoWay . '"/>
                                    </BookingClassAvails>
                                </FlightSegment>
                            </OriginDestinationOption>';
        }
        $xmlPart4 = '</OriginDestinationOptions>
                     </AirItinerary>';

        $xmlPart5 = '<PriceInfo>
                        <ItinTotalFare>
                             <BaseFare CurrencyCode="USD" DecimalPlaces="2" Amount="' . $baseFareOneWay . '"/>
                             <TotalFare CurrencyCode="USD" DecimalPlaces="2" Amount="' . $totalFareOneWay . '"/>
                        </ItinTotalFare>
                    </PriceInfo>';

        $xmlPart6 = '<TravelerInfo>';

        if ($adultsInfo != null) {
            foreach ($adultsInfo as $adult) {
                $xmlPart6 .= '<AirTraveler BirthDate="' . $adult['birthDate'] . '" PassengerTypeCode="ADT"
                                AccompaniedByInfantInd="' . $adult['with_infant'] . '" Gender="' . $adult['gender'] . '"
                                TravelerNationality="' . $adult['nationality'] . '">
                                <PersonName>
                                    <NamePrefix>' . $adult['prefix'] . '</NamePrefix>
                                    <GivenName>' . $adult['firs_name'] . '</GivenName>
                                    <Surname>' . $adult['last_name'] . '</Surname>
                                </PersonName>
                                <TravelerRefNumber RPH="' . $adult['ref_number'] . '"/>
                                <Document DocID="' . $adult['doc_id'] . '" DocType="' . $adult['doc_type'] . '"
                                ExpireDate="' . $adult['doc_expire'] . '"
                                EffectiveDate="' . $adult['doc_date'] . '" DocIssueCountry="' . $adult['doc_issue_country'] . '"
                                DocHolderNationality="' . $adult['doc_holder_country'] . '"/>
                            </AirTraveler>';
            }
        }

        if ($childrenInfo != null) {
            foreach ($childrenInfo as $child) {
                $xmlPart6 .= '<AirTraveler BirthDate="' . $child['birthDate'] . '" PassengerTypeCode="CHD"
                                AccompaniedByInfantInd="' . $child['with_infant'] . '" Gender="' . $child['gender'] . '"
                                TravelerNationality="' . $child['nationality'] . '">
                                <PersonName>
                                    <NamePrefix>' . $child['prefix'] . '</NamePrefix>
                                    <GivenName>' . $child['firs_name'] . '</GivenName>
                                    <Surname>' . $child['last_name'] . '</Surname>
                                </PersonName>
                                <TravelerRefNumber RPH="' . $child['ref_number'] . '"/>
                                <Document DocID="' . $child['doc_id'] . '" DocType="' . $child['doc_type'] . '"
                                ExpireDate="' . $child['doc_expire'] . '"
                                EffectiveDate="' . $child['doc_date'] . '" DocIssueCountry="' . $child['doc_issue_country'] . '"
                                DocHolderNationality="' . $child['doc_holder_country'] . '"/>
                            </AirTraveler>';
            }
        }

        if ($infantsInfo != null) {
            foreach ($infantsInfo as $infant) {
                $xmlPart6 .= '<AirTraveler BirthDate="' . $infant['birthDate'] . '" PassengerTypeCode="CHD"
                                AccompaniedByInfantInd="' . $infant['with_infant'] . '" Gender="' . $infant['gender'] . '"
                                TravelerNationality="' . $infant['nationality'] . '">
                                <PersonName>
                                    <GivenName>' . $infant['firs_name'] . '</GivenName>
                                    <Surname>' . $infant['last_name'] . '</Surname>
                                </PersonName>
                                <TravelerRefNumber RPH="' . $infant['ref_number'] . '"/>
                                <Document DocID="' . $infant['doc_id'] . '" DocType="' . $infant['doc_type'] . '"
                                ExpireDate="' . $infant['doc_expire'] . '"
                                EffectiveDate="' . $infant['doc_date'] . '" DocIssueCountry="' . $infant['doc_issue_country'] . '"
                                DocHolderNationality="' . $infant['doc_holder_country'] . '"/>
                            </AirTraveler>';
            }
        }

        $xmlPart6 .= '</TravelerInfo>';

        $xmlPart7 = '<ContactPerson>
                            <PersonName>
                                <GivenName>' . $adultsInfo[0]['firs_name'] . '</GivenName>
                                <Surname>' . $adultsInfo[0]['last_name'] . '</Surname>
                            </PersonName>
                          <Telephone PhoneNumber="' . $adultsInfo[0]['phone'] . '"/>
                          <HomeTelephone PhoneNumber="' . $adultsInfo[0]['phone'] . '"/>
                          <Email>' . $adultsInfo[0]['email'] . '</Email>
                    </ContactPerson>';

        $xmlPart8 = '<Ticketing TicketingStatus="1"/>';
        if ($initialBooking) {
            $xmlPart8 .= '<BookingReferenceID Status="' . $status . '" Instance="' . $instance . '" ID="' . $PNR . '" ID_Context="BookingRef"/>';
        }
        $xmlPart8 .= '</OTA_AirBookRQ>';
        return $xmlPart1 . $xmlPart2 . $xmlPart3 . $xmlPart4 . $xmlPart5 . $xmlPart6 . $xmlPart7 . $xmlPart8;

    }

    public function readBooking($PNR)
    {
        $response = Http::withHeaders([
            'Accept' => 'application/xml',
            'Content-Type' => 'application/xml',
            'Authorization' => config('app.AgencyPassword')
        ])->send('post', config('app.TargetURL') . 'booking/read',
            [
                'body' => $this->getSchemaReadBooking($PNR),
            ]);

        $xml = simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOBLANKS);

        return json_encode($xml);
    }


    public function getSchemaReadBooking($PNR)
    {
        $xml = '<OTA_ReadRQ xmlns="http://www.opentravel.org/OTA/2003/05"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05
            OTA_AirBookRQ.xsd" EchoToken="50987" TimeStamp="2022-03-23T05:44:10+05:30"
            Target="' . config("app.Target") . '"
            Version="2.001" SequenceNmbr="' . config('app.SequenceNmbr') . '" PrimaryLangID="En-us">
            <POS>
                <Source
                AirlineVendorID="' . config("app.AirlineVendorID") . '"
                ISOCountry="' . config("app.ISOCountry") . '"
                ISOCurrency="' . config("app.ISOCurrency") . '">
                    <RequestorID Type="5" ID="' . config("app.AgencyID") . '" AgentCode="" IATACode="" AccountNumber=""/>
                </Source>
            </POS>';

        $xml .= '<UniqueID ID="'.$PNR.'"/>';
        $xml .= '</OTA_ReadRQ>';

        return $xml;
    }
}